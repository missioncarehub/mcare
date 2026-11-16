<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\AlumniProfile;
use App\Models\CareerInquiry;
use App\Models\ClassroomComment;
use App\Models\DeletedAccountEmail;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeAttendance;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use App\Models\User;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountDeletionService
{
    public function __construct(private ProfilePhotoStore $profilePhotos) {}

    /**
     * Permanently remove an operational account and its enrollment artifacts.
     *
     * @return array{label: string, email: string, role: string}
     */
    public function delete(User $user, User $actor): array
    {
        abort_unless(in_array($user->role, ['trainer', 'trainee', 'applicant', 'alumni'], true), 404);

        if ($user->is($actor) || $user->hasRole('admin') || $user->role === 'admin') {
            throw ValidationException::withMessages([
                'account' => 'Administrator accounts cannot be deleted here.',
            ]);
        }

        $accountLabel = $user->name ?: 'Applicant';
        $accountEmail = $user->email;
        $accountRole = $user->role;

        $applications = EnrollmentApplication::where('user_id', $user->id)
            ->orWhere('email', $accountEmail)
            ->get();
        $historicalClaim = $user->historicalAlumniClaim()->first();
        $isHistoricalAlumni = (bool) (
            $applications->contains(fn ($app) => (bool) $app->is_historical_record)
            || $historicalClaim?->status === HistoricalAlumniClaim::STATUS_APPROVED
        );

        $storageFiles = collect();

        if ($historicalClaim) {
            $storageFiles = $storageFiles->merge([
                $historicalClaim->evidence_document_path,
                $historicalClaim->evidence_document_page_2_path,
            ])->filter();
        }

        foreach ($applications as $app) {
            $storageFiles = $storageFiles->merge([
                $app->birth_certificate_path,
                $app->education_document_path,
                $app->good_moral_certificate_path,
                $app->id_photo_path,
                $app->signature_path,
            ])->filter();

            $storageFiles = $storageFiles->merge(
                $app->paymentTransactions()->pluck('receipt_proof_path')->filter()
            );
        }

        $officialFiles = collect();
        foreach ($applications as $app) {
            $docs = $app->officialDocuments()
                ->whereNotNull('file_path')
                ->get(['storage_disk', 'file_path']);
            $officialFiles = $officialFiles->merge($docs);
        }

        $queuedJobIds = $this->queuedNotificationJobIds($user);

        DB::transaction(function () use ($user, $applications, $historicalClaim, $actor, $accountRole, $accountEmail, $queuedJobIds, $isHistoricalAlumni): void {
            if ($queuedJobIds !== []) {
                DB::table('jobs')->whereIn('id', $queuedJobIds)->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }

            foreach ($applications as $app) {
                $app->officialDocuments()->delete();

                $recordIds = $app->competencyRecords()->pluck('id');
                TraineeOutcomeResult::whereIn('trainee_competency_record_id', $recordIds)->delete();
                $app->competencyRecords()->delete();

                $app->quizAttempts()->delete();
                $app->moduleProgress()->delete();
                TrainingSubmoduleProgress::where('enrollment_application_id', $app->id)->delete();
                TraineeAttendance::where('enrollment_application_id', $app->id)->delete();

                $app->paymentTransactions()->delete();
                $app->paymentAttempts()->delete();
                $app->targetedQuizzes()->update(['target_enrollment_application_id' => null]);
                TrainingModule::where('target_enrollment_application_id', $app->id)
                    ->update(['target_enrollment_application_id' => null]);

                $app->delete();
            }

            // Remove any pending/approved admission applications so user can re-apply cleanly
            AdmissionApplication::where('email', $accountEmail)->delete();

            // Clear any lingering deleted account block hashes
            DeletedAccountEmail::where('email_hash', DeletedAccountEmail::hashFor($accountEmail))->delete();

            // Clear classroom comments authored by or directed to this user
            ClassroomComment::where('author_id', $user->id)
                ->orWhere('recipient_user_id', $user->id)
                ->delete();

            // Clear career hub inquiries
            CareerInquiry::where('user_id', $user->id)
                ->orWhere('email', $accountEmail)
                ->delete();

            $historicalClaim?->delete();

            if ($accountRole === 'trainer') {
                TrainingBatch::where('trainer_id', $user->id)->update(['trainer_id' => null]);
                TrainerAnnouncement::where('trainer_id', $user->id)->delete();

                $quizzes = Quiz::where('trainer_id', $user->id)->get();
                foreach ($quizzes as $quiz) {
                    QuizQuestion::where('quiz_id', $quiz->id)->delete();
                    QuizAttempt::where('quiz_id', $quiz->id)->delete();
                    TraineeAttendance::where('quiz_id', $quiz->id)->update(['quiz_id' => null]);
                    ClassroomComment::where('commentable_type', Quiz::class)->where('commentable_id', $quiz->id)->delete();
                    $quiz->delete();
                }

                $modules = TrainingModule::where('trainer_id', $user->id)->get();
                foreach ($modules as $module) {
                    $submoduleIds = TrainingSubmodule::where('training_module_id', $module->id)->pluck('id');
                    TrainingSubmoduleProgress::whereIn('training_submodule_id', $submoduleIds)->delete();
                    TrainingSubmodule::where('training_module_id', $module->id)->delete();

                    ModuleProgress::where('training_module_id', $module->id)->delete();
                    TraineeOutcomeResult::where('training_module_id', $module->id)->update(['training_module_id' => null]);
                    ClassroomComment::where('commentable_type', TrainingModule::class)->where('commentable_id', $module->id)->delete();
                    $module->delete();
                }
            }

            AlumniProfile::where('user_id', $user->id)->delete();

            AdminActivityLog::record($actor, 'admin.account.deleted', $user, [
                'deleted_role' => $accountRole,
                'deleted_email' => $accountEmail,
                'historical_alumni_record' => $isHistoricalAlumni,
            ]);

            $user->syncRoles([]);
            $user->delete();
        });

        foreach ($storageFiles->unique() as $path) {
            Storage::disk('local')->delete($path);
        }
        foreach ($officialFiles as $doc) {
            Storage::disk($doc->storage_disk ?: 'local')->delete($doc->file_path);
        }
        $this->profilePhotos->deleteFor($user);

        return [
            'label' => $accountLabel,
            'email' => $accountEmail,
            'role' => $accountRole,
        ];
    }

    /** @return list<int> */
    private function queuedNotificationJobIds(User $user): array
    {
        if (! Schema::hasTable('jobs')) {
            return [];
        }

        return DB::table('jobs')
            ->get(['id', 'payload'])
            ->filter(function (object $job) use ($user): bool {
                $payload = json_decode($job->payload, true);
                if (data_get($payload, 'data.commandName') !== SendQueuedNotifications::class) {
                    return false;
                }

                try {
                    $command = unserialize((string) data_get($payload, 'data.command'));
                } catch (Throwable) {
                    return false;
                }

                return $command instanceof SendQueuedNotifications
                    && collect($command->notifiables)->contains(
                        fn ($notifiable): bool => $notifiable instanceof User && $notifiable->is($user),
                    );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
