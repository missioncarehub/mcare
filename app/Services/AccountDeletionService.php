<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\AlumniProfile;
use App\Models\HistoricalAlumniClaim;
use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
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

        $application = $user->enrollmentApplication()->first();
        $historicalClaim = $user->historicalAlumniClaim()->first();
        $isHistoricalAlumni = (bool) ($application?->is_historical_record
            || $historicalClaim?->status === HistoricalAlumniClaim::STATUS_APPROVED);

        $storageFiles = collect();

        if ($historicalClaim) {
            $storageFiles = $storageFiles->merge([
                $historicalClaim->evidence_document_path,
                $historicalClaim->evidence_document_page_2_path,
            ])->filter();
        }

        if ($application) {
            $storageFiles = $storageFiles->merge([
                $application->birth_certificate_path,
                $application->education_document_path,
                $application->good_moral_certificate_path,
                $application->id_photo_path,
                $application->signature_path,
            ])->filter();

            $storageFiles = $storageFiles->merge(
                $application->paymentTransactions()->pluck('receipt_proof_path')->filter()
            );
        }

        $officialFiles = $application
            ? $application->officialDocuments()
                ->whereNotNull('file_path')
                ->get(['storage_disk', 'file_path'])
            : collect();

        $queuedJobIds = $this->queuedNotificationJobIds($user);

        DB::transaction(function () use ($user, $application, $historicalClaim, $actor, $accountRole, $accountEmail, $queuedJobIds, $isHistoricalAlumni): void {
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

            if ($application) {
                $application->officialDocuments()->delete();
                $application->competencyRecords()->delete();
                $application->quizAttempts()->delete();
                $application->moduleProgress()->delete();
                $application->paymentTransactions()->delete();
                $application->paymentAttempts()->delete();
                $application->targetedQuizzes()->update(['target_enrollment_application_id' => null]);
                TrainingModule::where('target_enrollment_application_id', $application->id)
                    ->update(['target_enrollment_application_id' => null]);

                $application->delete();
            }

            $historicalClaim?->delete();

            if ($accountRole === 'trainer') {
                TrainingBatch::where('trainer_id', $user->id)->update(['trainer_id' => null]);
                TrainerAnnouncement::where('trainer_id', $user->id)->delete();
                Quiz::where('trainer_id', $user->id)->delete();
                TrainingModule::where('trainer_id', $user->id)->delete();
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
