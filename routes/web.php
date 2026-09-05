<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminCareerHubController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLearningSystemController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdmissionApplicationReviewController;
use App\Http\Controllers\Admin\BatchScheduleController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use App\Http\Controllers\Admin\HistoricalAlumniClaimController as AdminHistoricalAlumniClaimController;
use App\Http\Controllers\Admin\PaymentScheduleController;
use App\Http\Controllers\Admin\PublicUpdateController;
use App\Http\Controllers\Admin\TrainingProgramController;
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\Alumni\AlumniCareerHubController;
use App\Http\Controllers\Auth\AccountSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ClassroomCommentController;
use App\Http\Controllers\CompetencyWorkbookController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentPaymentController;
use App\Http\Controllers\HistoricalAlumniClaimController;
use App\Http\Controllers\LandingChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhilippineAddressController;
use App\Http\Controllers\Trainee\CertificateController as TraineeCertificateController;
use App\Http\Controllers\Trainee\QuizAttemptController as TraineeQuizAttemptController;
use App\Http\Controllers\Trainee\QuizController as TraineeQuizController;
use App\Http\Controllers\Trainee\TraineeDashboardController;
use App\Http\Controllers\Trainer\AnnouncementController as TrainerAnnouncementController;
use App\Http\Controllers\Trainer\AttendanceController;
use App\Http\Controllers\Trainer\CompetencyRecordController as TrainerCompetencyRecordController;
use App\Http\Controllers\Trainer\QuizController as TrainerQuizController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerPortalController;
use App\Http\Controllers\Trainer\TrainerSearchController;
use App\Http\Controllers\Trainer\TrainingModuleController as TrainerTrainingModuleController;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\PublicSiteSetting;
use App\Models\PublicUpdate;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Route;

/*
 * Every web route receives a generous coarse limiter. This is NOT the main
 * defense against injection; it only slows abusive request floods. Sensitive
 * endpoints below add stricter limiters on top of this global baseline.
 */
Route::middleware('throttle:global-web')->group(function () {
    Route::get('/', function () {
        $applicationId = request()->session()->get('enrollment.payment_application_id');
        $applicationProgress = is_numeric($applicationId)
            ? EnrollmentApplication::query()->whereKey((int) $applicationId)->first()
            : null;
        $programs = TrainingProgram::query()->active()->orderBy('name')->get();
        $publicUpdates = PublicUpdate::query()->forLanding()->get();
        $socialLinks = PublicSiteSetting::current()->socialLinks();

        return view('landing.home', compact('applicationProgress', 'programs', 'publicUpdates', 'socialLinks'));
    })->name('landing');

    Route::post('/landing/chat', [LandingChatController::class, 'store'])
        ->middleware('throttle:landing-chat')
        ->name('landing.chat');

    /*
     * OAuth callback URLs can temporarily contain authorization parameters.
     * Mark both directions no-store/noindex in addition to rate limiting them.
     */
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
        ->middleware(['throttle:oauth', 'private.response'])
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware(['throttle:oauth', 'private.response'])
        ->name('auth.google.callback');

    Route::get('/login', [AccountSessionController::class, 'create'])
        ->middleware('private.response')
        ->name('login');

    Route::post('/login', [AccountSessionController::class, 'store'])
        ->middleware(['throttle:admin-login', 'private.response'])
        ->name('login.store');

    Route::post('/login/verify-2fa', [AdminSessionController::class, 'verifyTwoFactor'])
        ->middleware(['throttle:admin-login', 'private.response'])
        ->name('login.verify-2fa');

    Route::post('/logout', [AccountSessionController::class, 'destroy'])
        ->middleware('throttle:sensitive-mutation')
        ->name('logout');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1', 'private.response'])
        ->name('verification.verify');

    Route::get('/alumni/claim', [HistoricalAlumniClaimController::class, 'create'])
        ->middleware('private.response')
        ->name('alumni.claim.create');
    Route::post('/alumni/claim', [HistoricalAlumniClaimController::class, 'store'])
        ->middleware(['throttle:3,1', 'private.response'])
        ->name('alumni.claim.store');
    Route::get('/alumni/claim/received', [HistoricalAlumniClaimController::class, 'received'])
        ->middleware('private.response')
        ->name('alumni.claim.received');

    Route::prefix('account')
        ->name('account.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/settings', [AccountSettingsController::class, 'show'])->name('settings');
            Route::get('/help', [AccountSettingsController::class, 'help'])->name('help');
            Route::patch('/avatar', [AccountSettingsController::class, 'updateAvatar'])
                ->middleware('throttle:sensitive-mutation')
                ->name('avatar.update');
            Route::patch('/password', [AccountSettingsController::class, 'updatePassword'])
                ->middleware('throttle:sensitive-mutation')
                ->name('password.update');
            Route::patch('/registrar', [AccountSettingsController::class, 'updateRegistrar'])
                ->middleware('throttle:sensitive-mutation')
                ->name('registrar.update');
            Route::get('/registrar-signature', [AccountSettingsController::class, 'registrarSignature'])
                ->name('registrar.signature');
            Route::post('/security-event', [AccountSettingsController::class, 'securityEvent'])
                ->middleware('throttle:sensitive-mutation')
                ->name('security-event');
        });

    Route::prefix('notifications')
        ->name('notifications.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])
                ->middleware('throttle:sensitive-mutation')
                ->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])
                ->middleware('throttle:sensitive-mutation')
                ->name('read-all');
        });

    Route::prefix('classroom-comments')
        ->name('classroom-comments.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/{type}/{id}', [ClassroomCommentController::class, 'index'])
                ->whereIn('type', ['module', 'quiz'])
                ->whereNumber('id')
                ->name('index');
            Route::post('/', [ClassroomCommentController::class, 'store'])
                ->middleware('throttle:sensitive-mutation')
                ->name('store');
            Route::patch('/{comment}', [ClassroomCommentController::class, 'update'])
                ->middleware('throttle:sensitive-mutation')
                ->name('update');
            Route::delete('/{comment}', [ClassroomCommentController::class, 'destroy'])
                ->middleware('throttle:sensitive-mutation')
                ->name('destroy');
        });

    /*
     * Enrollment can display a signed-in applicant's saved profile, so it gets
     * no-cache/no-index response headers even though the form is publicly reachable.
     */
    Route::middleware('private.response')->group(function () {
        Route::get('/applications', [AdmissionApplicationController::class, 'create'])
            ->name('applications.create');
        Route::post('/applications', [AdmissionApplicationController::class, 'store'])
            ->middleware('throttle:3,1')
            ->name('applications.store');
        Route::get('/applications/received', [AdmissionApplicationController::class, 'received'])
            ->name('applications.received');
        Route::get('/applications/status', [AdmissionApplicationController::class, 'status'])
            ->name('applications.status');
        Route::post('/applications/status', [AdmissionApplicationController::class, 'lookup'])
            ->middleware('throttle:search')
            ->name('applications.lookup');

        Route::get('/enrollment', [EnrollmentController::class, 'create'])
            ->name('enrollment.create');
        Route::post('/enrollment/unlock', [EnrollmentController::class, 'unlock'])
            ->middleware('throttle:search')
            ->name('enrollment.unlock');

        Route::get('/enrollment/address/regions', [PhilippineAddressController::class, 'regions'])
            ->middleware('throttle:address-lookup')
            ->name('enrollment.address.regions');
        Route::get('/enrollment/address/provinces', [PhilippineAddressController::class, 'provinces'])
            ->middleware('throttle:address-lookup')
            ->name('enrollment.address.provinces');
        Route::get('/enrollment/address/cities', [PhilippineAddressController::class, 'cities'])
            ->middleware('throttle:address-lookup')
            ->name('enrollment.address.cities');
        Route::get('/enrollment/address/barangays', [PhilippineAddressController::class, 'barangays'])
            ->middleware('throttle:address-lookup')
            ->name('enrollment.address.barangays');

        Route::post('/enrollment', [EnrollmentController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('enrollment.store');

        Route::get('/enrollment/drafts/{field}/content', [EnrollmentController::class, 'draftContent'])
            ->middleware('throttle:document-downloads')
            ->name('enrollment.drafts.content');

        Route::get('/payments', [EnrollmentPaymentController::class, 'payments'])
            ->name('payments.show');
        Route::post('/payments', [EnrollmentPaymentController::class, 'lookup'])
            ->middleware('throttle:search')
            ->name('payments.lookup');

        Route::middleware(['enrollment.payment.access'])->group(function () {
            Route::get('/payment', [EnrollmentPaymentController::class, 'show'])
                ->name('payment.show');

            Route::post('/payment', [EnrollmentPaymentController::class, 'select'])
                ->middleware(['throttle:6,1'])
                ->name('payment.select');

            Route::get('/payment/return', [EnrollmentPaymentController::class, 'returned'])
                ->middleware(['throttle:20,1'])
                ->name('payment.return');

            Route::get('/payment/cancel', [EnrollmentPaymentController::class, 'cancelled'])
                ->middleware(['throttle:20,1'])
                ->name('payment.cancel');

            Route::get('/payment/status', [EnrollmentPaymentController::class, 'status'])
                ->middleware(['throttle:30,1'])
                ->name('payment.status');

            Route::get('/payment/complete', [EnrollmentPaymentController::class, 'completed'])
                ->middleware(['throttle:30,1'])
                ->name('payment.complete');

            Route::get('/payment/receipt', [EnrollmentPaymentController::class, 'receipt'])
                ->middleware(['throttle:20,1'])
                ->name('payment.receipt');

            Route::get('/payment/receipt/download', [EnrollmentPaymentController::class, 'downloadReceipt'])
                ->middleware(['throttle:document-downloads'])
                ->name('payment.receipt.download');
        });
    });

    /*
     * The entire admin area receives privacy headers. Authentication and role
     * checks remain the real access control; noindex/robots headers are only
     * additional privacy and search-engine hygiene.
     */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'admin', 'two-factor', 'permission:admin.access'])->group(function () {
                Route::get('/', AdminDashboardController::class)->name('dashboard');

                Route::get('/applications', [AdmissionApplicationReviewController::class, 'index'])
                    ->middleware(['permission:enrollments.review', 'throttle:search'])
                    ->name('applications.index');

                Route::get('/applications/{admissionApplication}', [AdmissionApplicationReviewController::class, 'show'])
                    ->middleware('permission:enrollments.review')
                    ->name('applications.show');

                Route::patch('/applications/{admissionApplication}', [AdmissionApplicationReviewController::class, 'update'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('applications.update');

                Route::delete('/applications/{admissionApplication}', [AdmissionApplicationReviewController::class, 'destroy'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('applications.destroy');

                Route::get('/enrollments', [EnrollmentReviewController::class, 'index'])
                    ->middleware(['permission:enrollments.review', 'throttle:search'])
                    ->name('enrollments.index');

                Route::get('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'show'])
                    ->middleware('permission:enrollments.review')
                    ->name('enrollments.show');

                Route::patch('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'update'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('enrollments.update');

                Route::delete('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'destroy'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('enrollments.destroy');

                Route::get('/enrollments/{enrollmentApplication}/photo', [EnrollmentReviewController::class, 'photo'])
                    ->middleware('permission:enrollments.review')
                    ->name('enrollments.photo');

                Route::get('/enrollments/{enrollmentApplication}/tesda-form', [EnrollmentReviewController::class, 'tesdaForm'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.tesda-form');

                Route::get('/enrollments/{enrollmentApplication}/document-review', [EnrollmentReviewController::class, 'documentReview'])
                    ->middleware('permission:enrollments.review')
                    ->name('enrollments.document-review');

                Route::patch('/enrollments/{enrollmentApplication}/documents/review', [EnrollmentReviewController::class, 'updateDocumentReview'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('enrollments.documents.review');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}', [EnrollmentReviewController::class, 'documentPreview'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.documents.show');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}/content', [EnrollmentReviewController::class, 'documentContent'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.documents.content');

                Route::redirect('/public-updates', '/admin/public-settings');

                Route::get('/public-settings', [PublicUpdateController::class, 'index'])
                    ->middleware('permission:announcements.manage')
                    ->name('public-settings.index');

                Route::get('/public-settings/{publicUpdate}/edit', [PublicUpdateController::class, 'edit'])
                    ->middleware('permission:announcements.manage')
                    ->name('public-settings.edit');

                Route::post('/public-settings', [PublicUpdateController::class, 'store'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('public-settings.store');

                Route::patch('/public-settings/social', [PublicUpdateController::class, 'updateSocial'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('public-settings.social');

                Route::patch('/public-settings/{publicUpdate}', [PublicUpdateController::class, 'update'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('public-settings.update');

                Route::delete('/public-settings/{publicUpdate}', [PublicUpdateController::class, 'destroy'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('public-settings.destroy');

                Route::get('/programs', [TrainingProgramController::class, 'index'])
                    ->middleware('permission:schedules.manage')
                    ->name('training-programs.index');

                Route::get('/programs/{trainingProgram}/edit', [TrainingProgramController::class, 'edit'])
                    ->middleware('permission:schedules.manage')
                    ->name('training-programs.edit');

                Route::post('/training-programs', [TrainingProgramController::class, 'store'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('training-programs.store');

                Route::patch('/training-programs/{trainingProgram}', [TrainingProgramController::class, 'update'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('training-programs.update');

                Route::delete('/training-programs/{trainingProgram}', [TrainingProgramController::class, 'destroy'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('training-programs.destroy');

                Route::get('/batches', [BatchScheduleController::class, 'index'])
                    ->middleware('permission:schedules.manage')
                    ->name('batches.index');

                Route::post('/batches', [BatchScheduleController::class, 'store'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('batches.store');

                Route::get('/batches/{trainingBatch}/edit', [BatchScheduleController::class, 'edit'])
                    ->middleware('permission:schedules.manage')
                    ->name('batches.edit');

                Route::patch('/batches/{trainingBatch}', [BatchScheduleController::class, 'update'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('batches.update');

                Route::delete('/batches/{trainingBatch}', [BatchScheduleController::class, 'destroy'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('batches.destroy');

                Route::get('/schedules', [BatchScheduleController::class, 'calendar'])
                    ->middleware('permission:schedules.manage')
                    ->name('schedules.index');

                Route::get('/payment-scheduling', [PaymentScheduleController::class, 'index'])
                    ->middleware('permission:payments.verify')
                    ->name('payment-schedules.index');

                Route::get('/payment-scheduling/lookup', [PaymentScheduleController::class, 'lookupEnrollee'])
                    ->middleware(['permission:payments.verify', 'throttle:search'])
                    ->name('payment-schedules.lookup');

                Route::patch('/payment-scheduling/{enrollmentApplication}', [PaymentScheduleController::class, 'update'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.update');

                Route::post('/payment-scheduling/{enrollmentApplication}/transactions', [PaymentScheduleController::class, 'storeTransaction'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.transactions.store');

                Route::patch('/payment-scheduling/transactions/{transaction}/verify', [PaymentScheduleController::class, 'verifyTransaction'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.transactions.verify');
                Route::get('/payment-scheduling/transactions/{transaction}/proof', [PaymentScheduleController::class, 'receiptProof'])
                    ->middleware(['permission:payments.verify', 'throttle:document-downloads'])
                    ->name('payment-schedules.transactions.proof');

                Route::get('/announcements', [AdminAnnouncementController::class, 'index'])
                    ->middleware('permission:announcements.view')
                    ->name('announcements.index');

                Route::post('/announcements', [AdminAnnouncementController::class, 'store'])
                    ->middleware(['permission:announcements.view', 'throttle:sensitive-mutation'])
                    ->name('announcements.store');

                Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])
                    ->middleware(['permission:announcements.view', 'throttle:sensitive-mutation'])
                    ->name('announcements.destroy');

                Route::get('/learning/trainees', [AdminLearningSystemController::class, 'trainees'])
                    ->middleware('permission:trainees.manage')
                    ->name('learning.trainees');
                Route::get('/learning/trainees/export', [AdminLearningSystemController::class, 'exportTrainees'])
                    ->middleware(['permission:reports.export', 'throttle:document-downloads'])
                    ->name('learning.trainees.export');
                Route::get('/learning/trainees/{enrollmentApplication}', [AdminLearningSystemController::class, 'showTrainee'])
                    ->middleware('permission:trainees.manage')
                    ->name('learning.trainees.show');
                Route::patch('/learning/trainees/{enrollmentApplication}/status', [AdminLearningSystemController::class, 'updateTraineeStatus'])
                    ->middleware(['permission:trainees.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.trainees.status');
                Route::delete('/learning/trainees/{enrollmentApplication}', [AdminLearningSystemController::class, 'destroyTrainee'])
                    ->middleware(['permission:trainees.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.trainees.destroy');
                Route::get('/learning/attendance', [AdminAttendanceController::class, 'index'])
                    ->middleware('permission:trainees.manage')
                    ->name('learning.attendance');
                Route::post('/learning/attendance', [AdminAttendanceController::class, 'store'])
                    ->middleware(['permission:trainees.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.attendance.store');
                Route::get('/learning/attendance/export/{batch}', [AdminAttendanceController::class, 'export'])
                    ->middleware(['permission:reports.export', 'throttle:document-downloads'])
                    ->name('learning.attendance.export');
                Route::get('/learning/modules', [AdminLearningSystemController::class, 'modules'])
                    ->middleware('permission:modules.manage')
                    ->name('learning.modules');
                Route::post('/learning/modules/store', [AdminLearningSystemController::class, 'storeModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.store');
                Route::post('/learning/modules', [AdminLearningSystemController::class, 'storeModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation']);
                Route::get('/learning/modules/{module}/preview', [AdminLearningSystemController::class, 'previewModule'])
                    ->middleware('permission:modules.manage')
                    ->name('learning.modules.preview');
                Route::get('/learning/modules/{module}/content', [AdminLearningSystemController::class, 'moduleContent'])
                    ->middleware(['permission:modules.manage', 'throttle:document-downloads'])
                    ->name('learning.modules.content');
                Route::get('/learning/modules/{module}/download', [AdminLearningSystemController::class, 'downloadModule'])
                    ->middleware(['permission:modules.manage', 'throttle:document-downloads'])
                    ->name('learning.modules.download');
                Route::patch('/learning/modules/{module}', [AdminLearningSystemController::class, 'updateModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.update');
                Route::post('/learning/modules/presets', [AdminLearningSystemController::class, 'storeCatalogUnit'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.presets.store');
                Route::patch('/learning/modules/presets/{competencyUnit}', [AdminLearningSystemController::class, 'updateCatalogUnit'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.presets.update');
                Route::delete('/learning/modules/presets/{competencyUnit}', [AdminLearningSystemController::class, 'destroyCatalogUnit'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.presets.destroy');
                Route::post('/learning/modules/outcomes', [AdminLearningSystemController::class, 'storeCatalogOutcome'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.outcomes.store');
                Route::patch('/learning/modules/outcomes/{competencyOutcome}', [AdminLearningSystemController::class, 'updateCatalogOutcome'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.outcomes.update');
                Route::delete('/learning/modules/outcomes/{competencyOutcome}', [AdminLearningSystemController::class, 'destroyCatalogOutcome'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.outcomes.destroy');
                Route::delete('/learning/modules/{module}', [AdminLearningSystemController::class, 'destroyModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.destroy');
                Route::get('/learning/certificates', [CertificationController::class, 'index'])
                    ->middleware('permission:official-documents.manage')
                    ->name('learning.certificates');
                Route::get('/learning/competency-records/excel', [CompetencyWorkbookController::class, 'downloadForAdmin'])
                    ->middleware(['permission:reports.export', 'throttle:document-downloads'])
                    ->name('learning.competency-workbooks.download');
                Route::post('/learning/certificates/{enrollmentApplication}/{type}', [CertificationController::class, 'generate'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->whereIn('type', OfficialDocument::supportedTypes())
                    ->name('learning.documents.generate');
                Route::patch('/learning/official-documents/{officialDocument}/release', [CertificationController::class, 'release'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.documents.release');
                Route::get('/learning/official-documents/{officialDocument}/preview', [CertificationController::class, 'preview'])
                    ->middleware(['permission:official-documents.manage', 'throttle:document-downloads'])
                    ->name('learning.documents.preview');
                Route::post('/learning/certificates/{enrollmentApplication}/{type}/reissue', [CertificationController::class, 'reissue'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->whereIn('type', OfficialDocument::supportedTypes())
                    ->name('learning.documents.reissue');
                Route::get('/learning/official-documents/{officialDocument}/download', [CertificationController::class, 'download'])
                    ->middleware(['permission:official-documents.manage', 'throttle:document-downloads'])
                    ->name('learning.documents.download');
                Route::post('/learning/batch-tor-exports', [CertificationController::class, 'requestBatchTorExport'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.batch-exports.store');
                Route::get('/learning/batch-tor-exports/{batchDocumentExport}/download', [CertificationController::class, 'downloadBatchExport'])
                    ->middleware(['permission:official-documents.manage', 'throttle:document-downloads'])
                    ->name('learning.batch-exports.download');
                Route::get('/learning/alumni-jobs', [AdminCareerHubController::class, 'index'])
                    ->middleware('permission:alumni.jobs.manage')
                    ->name('learning.alumni-jobs');
                Route::get('/learning/alumni-jobs/preview', [AdminCareerHubController::class, 'preview'])
                    ->middleware('permission:alumni.jobs.manage')
                    ->name('learning.alumni-jobs.preview');
                Route::post('/learning/alumni-jobs', [AdminCareerHubController::class, 'store'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.store');
                Route::patch('/learning/alumni-jobs/{careerOpportunity}', [AdminCareerHubController::class, 'update'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.update');
                Route::delete('/learning/alumni-jobs/{careerOpportunity}', [AdminCareerHubController::class, 'destroy'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.destroy');
                Route::patch('/learning/alumni-jobs/inquiries/{careerInquiry}', [AdminCareerHubController::class, 'updateInquiry'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.inquiries.update');
                Route::delete('/learning/alumni-jobs/inquiries/{careerInquiry}', [AdminCareerHubController::class, 'destroyInquiry'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.inquiries.destroy');
                Route::get('/learning/reports', [AdminLearningSystemController::class, 'reports'])->name('learning.reports');

                Route::get('/historical-alumni', [AdminHistoricalAlumniClaimController::class, 'index'])
                    ->middleware('permission:accounts.manage')
                    ->name('historical-alumni.index');
                Route::get('/historical-alumni/{historicalAlumniClaim}', [AdminHistoricalAlumniClaimController::class, 'show'])
                    ->middleware('permission:accounts.manage')
                    ->name('historical-alumni.show');
                Route::patch('/historical-alumni/{historicalAlumniClaim}', [AdminHistoricalAlumniClaimController::class, 'update'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('historical-alumni.update');
                Route::get('/historical-alumni/{historicalAlumniClaim}/evidence', [AdminHistoricalAlumniClaimController::class, 'evidence'])
                    ->middleware(['permission:accounts.manage', 'throttle:document-downloads'])
                    ->name('historical-alumni.evidence');

                Route::get('/accounts', [AdminAccountController::class, 'index'])
                    ->middleware('permission:accounts.manage')
                    ->name('accounts.index');
                Route::get('/accounts/{user}/photo', [AdminAccountController::class, 'photo'])
                    ->middleware('permission:accounts.manage')
                    ->whereNumber('user')
                    ->name('accounts.photo');
                Route::post('/accounts/trainers', [AdminAccountController::class, 'storeTrainer'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('accounts.trainers.store');
                Route::post('/accounts/trainees', [AdminAccountController::class, 'storeTrainee'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('accounts.trainees.store');
                Route::patch('/accounts/historical-alumni/{historicalAlumniClaim}', [AdminHistoricalAlumniClaimController::class, 'update'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('accounts.historical-alumni.update');
                Route::get('/accounts/historical-alumni/{historicalAlumniClaim}/evidence', [AdminHistoricalAlumniClaimController::class, 'evidence'])
                    ->middleware(['permission:accounts.manage', 'throttle:document-downloads'])
                    ->name('accounts.historical-alumni.evidence');
                Route::delete('/accounts/{user}', [AdminAccountController::class, 'destroy'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->whereNumber('user')
                    ->name('accounts.destroy');

                Route::get('/logs', [AdminActivityLogController::class, 'index'])
                    ->middleware(['permission:logs.view', 'throttle:search'])
                    ->name('logs.index');

                Route::get('/logs/print', [AdminActivityLogController::class, 'print'])
                    ->middleware(['permission:logs.view', 'throttle:document-downloads'])
                    ->name('logs.print');

                Route::get('/logs/export', [AdminActivityLogController::class, 'export'])
                    ->middleware(['permission:logs.view', 'permission:reports.export', 'throttle:document-downloads'])
                    ->name('logs.export');
            });
        });
    Route::prefix('trainer')
        ->name('trainer.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'trainer', 'permission:trainer.access'])->group(function () {
                Route::get('/', TrainerDashboardController::class)
                    ->name('dashboard');
                Route::get('/search', [TrainerSearchController::class, 'index'])
                    ->middleware('throttle:search')
                    ->name('search');
                Route::get('/search/suggest', [TrainerSearchController::class, 'suggest'])
                    ->middleware('throttle:search')
                    ->name('search.suggest');

                Route::get('/stream', [TrainerAnnouncementController::class, 'index'])
                    ->middleware('permission:announcements.manage')
                    ->name('stream');
                Route::post('/announcements', [TrainerAnnouncementController::class, 'store'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.store');
                Route::patch('/announcements/{announcement}', [TrainerAnnouncementController::class, 'update'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.update');
                Route::delete('/announcements/{announcement}', [TrainerAnnouncementController::class, 'destroy'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.destroy');

                Route::get('/trainings', [TrainerPortalController::class, 'trainings'])->name('trainings');
                Route::get('/trainees', [TrainerPortalController::class, 'trainees'])
                    ->middleware('permission:trainees.view')
                    ->name('trainees');
                Route::get('/trainees/export', [TrainerPortalController::class, 'exportTrainees'])
                    ->middleware(['permission:trainees.export', 'throttle:document-downloads'])
                    ->name('trainees.export');
                Route::get('/attendance', [AttendanceController::class, 'index'])
                    ->middleware('permission:trainees.view')
                    ->name('attendance.index');
                Route::post('/attendance', [AttendanceController::class, 'store'])
                    ->middleware(['permission:trainees.view', 'throttle:sensitive-mutation'])
                    ->name('attendance.store');
                Route::get('/attendance/export/{batch}', [AttendanceController::class, 'export'])
                    ->middleware(['permission:trainees.export', 'throttle:document-downloads'])
                    ->name('attendance.export');
                Route::get('/competency-records', [TrainerCompetencyRecordController::class, 'index'])
                    ->middleware('permission:competencies.assess')
                    ->name('competencies.index');
                Route::get('/competency-records/batches/{trainingBatch}/{chart}', [TrainerCompetencyRecordController::class, 'chart'])
                    ->whereIn('chart', ['progress', 'achievement'])
                    ->middleware(['permission:competencies.assess', 'throttle:document-downloads'])
                    ->name('competencies.chart');
                Route::get('/competency-records/batches/{trainingBatch}/excel', [CompetencyWorkbookController::class, 'downloadForTrainer'])
                    ->middleware(['permission:trainees.export', 'throttle:document-downloads'])
                    ->name('competencies.export');
                Route::patch('/competency-records/bulk', [TrainerCompetencyRecordController::class, 'bulkUpdate'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('competencies.bulk-update');
                Route::get('/competency-records/{enrollmentApplication}', [TrainerCompetencyRecordController::class, 'edit'])
                    ->middleware('permission:competencies.assess')
                    ->name('competencies.edit');
                Route::patch('/competency-records/{enrollmentApplication}', [TrainerCompetencyRecordController::class, 'update'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('competencies.update');
                Route::get('/sessions', [TrainerPortalController::class, 'sessions'])->name('sessions');
                Route::get('/assessments', [TrainerQuizController::class, 'index'])
                    ->middleware('permission:quizzes.manage')
                    ->name('assessments');
                Route::get('/quizzes/create', [TrainerQuizController::class, 'create'])
                    ->middleware('permission:quizzes.manage')
                    ->name('quizzes.create');
                Route::post('/quizzes', [TrainerQuizController::class, 'store'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.store');
                Route::get('/quizzes/{quiz}/edit', [TrainerQuizController::class, 'edit'])
                    ->middleware('permission:quizzes.manage')
                    ->name('quizzes.edit');
                Route::patch('/quizzes/{quiz}', [TrainerQuizController::class, 'update'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.update');
                Route::patch('/quizzes/{quiz}/publication', [TrainerQuizController::class, 'publication'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.publication');
                Route::delete('/quizzes/{quiz}', [TrainerQuizController::class, 'destroy'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.destroy');
                Route::get('/quizzes/{quiz}/results', [TrainerQuizController::class, 'results'])
                    ->middleware(['permission:quizzes.manage', 'permission:grades.view'])
                    ->name('quizzes.results');
                Route::get('/quizzes/{quiz}/attempts/{attempt}/download/{question}', [TrainerQuizController::class, 'downloadAttemptSubmission'])
                    ->middleware(['permission:quizzes.manage', 'permission:grades.view'])
                    ->name('quizzes.attempts.download');
                Route::get('/resources', [TrainerPortalController::class, 'resources'])
                    ->middleware('permission:modules.publish')
                    ->name('resources');
                Route::get('/certificates', [TrainerPortalController::class, 'certificates'])->name('certificates');
                Route::get('/reports', [TrainerPortalController::class, 'reports'])->name('reports');

                Route::post('/modules', [TrainerTrainingModuleController::class, 'store'])
                    ->middleware(['permission:modules.publish', 'throttle:8,1'])
                    ->name('modules.store');
                Route::patch('/modules/{module}', [TrainerTrainingModuleController::class, 'update'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->name('modules.update');
                Route::delete('/modules/{module}', [TrainerTrainingModuleController::class, 'destroy'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->name('modules.destroy');

                Route::post('/modules/{module}/evaluations', [TrainerTrainingModuleController::class, 'evaluate'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('modules.evaluate');
                Route::post('/modules/{module}/quizzes', [TrainerTrainingModuleController::class, 'storeQuiz'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('modules.quizzes.store');

                Route::get('/modules/{module}', [TrainerDashboardController::class, 'viewModule'])
                    ->middleware('permission:modules.publish')
                    ->name('modules.show');

                Route::get('/modules/{module}/content', [TrainerDashboardController::class, 'moduleContent'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->name('modules.content');
                Route::get('/modules/{module}/download', [TrainerDashboardController::class, 'moduleDownload'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->name('modules.download');
                Route::get('/modules/{module}/supplementary/{index}', [TrainerDashboardController::class, 'supplementaryDownload'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->whereNumber('index')
                    ->name('modules.supplementary.download');
                Route::delete('/modules/{module}/supplementary/{index}', [TrainerTrainingModuleController::class, 'destroySupplementary'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->whereNumber('index')
                    ->name('modules.supplementary.destroy');
            });
        });

    Route::prefix('trainee')
        ->name('trainee.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'trainee', 'permission:trainee.access'])->group(function () {
                Route::get('/', [TraineeDashboardController::class, 'index'])
                    ->name('dashboard');
                Route::get('/schedule', [TraineeDashboardController::class, 'schedule'])
                    ->name('schedule');
                Route::get('/documents', [TraineeDashboardController::class, 'documents'])
                    ->middleware('permission:documents.view')
                    ->name('documents');
                Route::get('/documents/cotc/{officialDocument}/download', [TraineeCertificateController::class, 'download'])
                    ->middleware(['permission:cotc.download', 'throttle:document-downloads'])
                    ->name('cotc.download');

                Route::middleware('active.training')->group(function () {
                    Route::get('/stream', [TraineeDashboardController::class, 'stream'])
                        ->middleware('permission:announcements.view')
                        ->name('stream');
                    Route::get('/modules', [TraineeDashboardController::class, 'modules'])
                        ->middleware('permission:modules.view')
                        ->name('modules.index');
                    Route::get('/payments', [TraineeDashboardController::class, 'payments'])
                        ->middleware('permission:payments.view')
                        ->name('payments');
                    Route::post('/payments/tickets', [TraineeDashboardController::class, 'generateOnsiteTicket'])
                        ->middleware(['permission:payments.view', 'throttle:sensitive-mutation'])
                        ->name('payments.tickets.store');
                    Route::post('/payments/upload-proof', [TraineeDashboardController::class, 'uploadPaymentProof'])
                        ->middleware(['permission:payments.view', 'throttle:sensitive-mutation'])
                        ->name('payments.upload-proof');
                    Route::get('/modules/{module}', [TraineeDashboardController::class, 'viewModule'])
                        ->middleware('permission:modules.view')
                        ->name('modules.show');
                    Route::get('/modules/{module}/content', [TraineeDashboardController::class, 'moduleContent'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->name('modules.content');
                    Route::get('/modules/{module}/download', [TraineeDashboardController::class, 'moduleDownload'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->name('modules.download');
                    Route::get('/modules/{module}/supplementary/{index}', [TraineeDashboardController::class, 'supplementaryDownload'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->whereNumber('index')
                        ->name('modules.supplementary.download');
                    Route::patch('/modules/{module}/progress', [TraineeDashboardController::class, 'updateProgress'])
                        ->middleware(['permission:progress.update', 'throttle:sensitive-mutation'])
                        ->name('modules.progress');
                    Route::patch('/modules/{module}/submodules/{submodule}/progress', [TraineeDashboardController::class, 'updateSubmoduleProgress'])
                        ->middleware(['permission:progress.update', 'throttle:sensitive-mutation'])
                        ->name('modules.submodules.progress');
                    Route::post('/modules/{module}/security-event', [TraineeDashboardController::class, 'securityEvent'])
                        ->middleware(['permission:modules.view', 'throttle:20,1'])
                        ->name('modules.security-event');
                    Route::get('/quizzes', [TraineeQuizController::class, 'index'])
                        ->middleware('permission:quizzes.take')
                        ->name('quizzes.index');
                    Route::get('/quizzes/{quiz}', [TraineeQuizController::class, 'show'])
                        ->middleware('permission:quizzes.take')
                        ->name('quizzes.show');
                    Route::post('/quizzes/{quiz}/attempts', [TraineeQuizController::class, 'start'])
                        ->middleware(['permission:quizzes.take', 'throttle:sensitive-mutation'])
                        ->name('quizzes.start');
                    Route::get('/quiz-attempts/{attempt}', [TraineeQuizAttemptController::class, 'show'])
                        ->middleware('permission:quizzes.take')
                        ->name('quiz-attempts.show');
                    Route::post('/quiz-attempts/{attempt}/submit', [TraineeQuizAttemptController::class, 'submit'])
                        ->middleware(['permission:quizzes.take', 'throttle:sensitive-mutation'])
                        ->name('quiz-attempts.submit');
                    Route::get('/quiz-attempts/{attempt}/result', [TraineeQuizAttemptController::class, 'result'])
                        ->middleware('permission:quizzes.take')
                        ->name('quiz-attempts.result');
                    Route::get('/quiz-attempts/{attempt}/download/{question}', [TraineeQuizAttemptController::class, 'downloadSubmission'])
                        ->middleware('permission:quizzes.take')
                        ->name('quiz-attempts.download');
                });

                Route::middleware('graduate')->group(function () {
                    Route::get('/grades', [TraineeDashboardController::class, 'grades'])
                        ->middleware('permission:grades.view')
                        ->name('grades');

                    Route::middleware('permission:alumni.jobs.view')->group(function () {
                        Route::get('/career-hub', [AlumniCareerHubController::class, 'index'])
                            ->name('career-hub');
                        Route::patch('/career-hub/availability', [AlumniCareerHubController::class, 'updateAvailability'])
                            ->middleware('throttle:sensitive-mutation')
                            ->name('career-hub.availability');
                        Route::post('/career-hub/{careerOpportunity}/contact', [AlumniCareerHubController::class, 'contact'])
                            ->middleware('throttle:sensitive-mutation')
                            ->name('career-hub.contact');
                    });
                });
            });
        });

    // Legacy URL aliases render the shared trainee-based Career Hub; they do not create a second portal.
    Route::get('/alumni', [AlumniCareerHubController::class, 'index'])
        ->middleware(['auth', 'trainee', 'graduate', 'permission:alumni.jobs.view', 'private.response'])
        ->name('alumni.dashboard');

    Route::patch('/alumni/availability', [AlumniCareerHubController::class, 'updateAvailability'])
        ->middleware(['auth', 'trainee', 'graduate', 'permission:alumni.jobs.view', 'throttle:sensitive-mutation', 'private.response'])
        ->name('alumni.availability.update');

    Route::post('/alumni/{careerOpportunity}/contact', [AlumniCareerHubController::class, 'contact'])
        ->middleware(['auth', 'trainee', 'graduate', 'permission:alumni.jobs.view', 'throttle:sensitive-mutation', 'private.response'])
        ->name('alumni.jobs.contact');
});
