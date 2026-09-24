<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AlumniProfile;
use App\Models\CareerInquiry;
use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Services\CareerGraduateNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCareerHubController extends Controller
{
    public function __construct(
        private readonly CareerGraduateNotifier $graduateNotifier,
    ) {}

    public function index(): View
    {
        return view('admin.learning.alumni-jobs', [
            'alumniAccounts' => $this->graduateQuery()->count(),
            'availableAlumni' => AlumniProfile::query()
                ->where('is_available_for_duty', true)
                ->whereHas('user.enrollmentApplication', fn ($query) => $this->graduateScope($query))
                ->count(),
            'publishedJobs' => CareerOpportunity::query()->visibleToAlumni()->count(),
            'draftJobs' => CareerOpportunity::query()->where('is_published', false)->count(),
            'jobs' => CareerOpportunity::query()
                ->with('creator')
                ->withCount('inquiries')
                ->latest('created_at')
                ->paginate(12),
            'inquiries' => CareerInquiry::query()
                ->with(['graduate', 'opportunity', 'reviewer'])
                ->latest()
                ->paginate(8, ['*'], 'inquiry_page'),
            'pendingInquiryCount' => CareerInquiry::query()
                ->where('status', CareerInquiry::STATUS_PENDING)
                ->count(),
            'inquiryStatuses' => CareerInquiry::statuses(),
            'alumniRoster' => $this->graduateQuery()
                ->with('alumniProfile')
                ->orderBy('name')
                ->simplePaginate(10, ['*'], 'alumni_page'),
            'patientGenders' => CareerOpportunity::patientGenders(),
            'mobilityStatuses' => CareerOpportunity::mobilityStatuses(),
        ]);
    }

    public function preview(Request $request): View
    {
        AdminActivityLog::record($request->user(), 'career.portal.previewed');

        return view('admin.learning.alumni-preview', [
            // Administrators see the exact published feed without impersonating an alumni account.
            'isAdminPreview' => true,
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->orderBy('estimated_start_date')
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => 0,
            'alumniProfile' => null,
            'contactedJobIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request, 'careerCreate');
        $shouldPublish = $request->boolean('is_published');

        $opportunity = CareerOpportunity::create([
            ...$this->careerAttributes($validated),
            ...$this->smsAttributes($request, $validated),
            ...$this->compatibilityFields(),
            'created_by_id' => $request->user()->id,
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish ? now() : null,
        ]);

        AdminActivityLog::record($request->user(), 'career.opportunity.created', $opportunity, [
            'estimated_start_date' => $opportunity->estimated_start_date?->toDateString(),
            'patient_gender' => $opportunity->patient_gender,
            'mobility_status' => $opportunity->mobility_status,
            'published' => $opportunity->is_published,
            'sms_mode' => $opportunity->sms_mode,
        ]);

        return back()->with('saved', $this->afterSave($opportunity, $shouldPublish, publishedNow: $shouldPublish));
    }

    public function update(Request $request, CareerOpportunity $careerOpportunity): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        $wasPublished = $careerOpportunity->is_published;
        $shouldPublish = $request->boolean('is_published');

        $careerOpportunity->fill([
            ...$this->careerAttributes($validated),
            ...$this->smsAttributes($request, $validated, $careerOpportunity),
            ...$this->compatibilityFields(),
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish
                ? ($careerOpportunity->published_at ?: now())
                : null,
        ]);

        // Retire legacy contact and free-form fields when an old listing is reviewed.
        $careerOpportunity->forceFill([
            'location' => null,
            'employment_type' => null,
            'requirements' => null,
            'application_url' => null,
            'application_email' => null,
            'application_deadline' => null,
        ])->save();

        AdminActivityLog::record($request->user(), 'career.opportunity.updated', $careerOpportunity, [
            'estimated_start_date' => $careerOpportunity->estimated_start_date?->toDateString(),
            'patient_gender' => $careerOpportunity->patient_gender,
            'mobility_status' => $careerOpportunity->mobility_status,
            'published' => $careerOpportunity->is_published,
        ]);

        return back()->with('saved', $this->afterSave(
            $careerOpportunity,
            $shouldPublish,
            publishedNow: ! $wasPublished && $shouldPublish,
        ));
    }

    public function destroy(Request $request, CareerOpportunity $careerOpportunity): RedirectResponse
    {
        $label = $careerOpportunity->listingTitle();

        AdminActivityLog::record($request->user(), 'career.opportunity.deleted', $careerOpportunity, [
            'title' => $label,
            'estimated_start_date' => $careerOpportunity->estimated_start_date?->toDateString(),
        ]);

        $careerOpportunity->delete();

        return back()->with('saved', "{$label} was removed.");
    }

    public function updateInquiry(Request $request, CareerInquiry $careerInquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(CareerInquiry::statuses()))],
            'admin_notes' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/u'],
        ]);

        $careerInquiry->fill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        if ($validated['status'] === CareerInquiry::STATUS_PENDING) {
            $careerInquiry->reviewed_at = null;
            $careerInquiry->reviewed_by_id = null;
        } else {
            $careerInquiry->reviewed_at = $careerInquiry->reviewed_at ?: now();
            $careerInquiry->reviewed_by_id = $request->user()->id;
        }

        $careerInquiry->save();
        $careerInquiry->loadMissing('opportunity');

        AdminActivityLog::record($request->user(), 'career.inquiry.updated', $careerInquiry, [
            'status' => $careerInquiry->status,
        ]);

        return back()->with('saved', 'Inquiry for '.$careerInquiry->opportunity?->listingTitle().' was updated.');
    }

    public function destroyInquiry(Request $request, CareerInquiry $careerInquiry): RedirectResponse
    {
        $label = $careerInquiry->opportunity?->listingTitle() ?: 'this career';

        AdminActivityLog::record($request->user(), 'career.inquiry.deleted', $careerInquiry, [
            'graduate' => $careerInquiry->name,
            'opportunity_id' => $careerInquiry->career_opportunity_id,
        ]);

        $careerInquiry->delete();

        return back()->with('saved', 'Inquiry for '.$label.' was removed.');
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request, ?string $errorBag = null): array
    {
        $safeText = ['not_regex:/[<>]/u'];

        $rules = [
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'estimated_salary' => ['required', 'string', 'max:80', ...$safeText],
            'estimated_start_date' => ['required', 'date', 'after_or_equal:today'],
            'patient_gender' => ['required', Rule::in(array_keys(CareerOpportunity::patientGenders()))],
            'mobility_status' => ['required', Rule::in(array_keys(CareerOpportunity::mobilityStatuses()))],
            'patient_age' => ['nullable', 'integer', 'between:0,120', 'required_without_all:specific_contraptions,condition_summary'],
            'specific_contraptions' => ['nullable', 'string', 'max:255', 'required_without_all:patient_age,condition_summary', ...$safeText],
            'condition_summary' => ['nullable', 'string', 'max:500', 'required_without_all:patient_age,specific_contraptions', ...$safeText],
            'is_published' => ['nullable', 'boolean'],
            'sms_send_immediately' => ['nullable', 'boolean'],
            'sms_scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];

        return $errorBag
            ? $request->validateWithBag($errorBag, $rules)
            : $request->validate($rules);
    }

    /** @return array<string, string> */
    private function compatibilityFields(): array
    {
        return [
            'employer' => 'MCARE-Coordinated Placement',
            'description' => 'Privacy-minimal career posting managed through the MCARE Alumni Hub.',
        ];
    }

    /** @param array<string, mixed> $validated */
    private function careerAttributes(array $validated): array
    {
        return collect($validated)->except([
            'is_published',
            'sms_send_immediately',
            'sms_scheduled_at',
        ])->all();
    }

    /** @param array<string, mixed> $validated */
    private function smsAttributes(Request $request, array $validated, ?CareerOpportunity $existing = null): array
    {
        if ($existing?->sms_sent_at) {
            return [
                'sms_mode' => $existing->sms_mode,
                'sms_scheduled_at' => $existing->sms_scheduled_at,
            ];
        }

        $immediate = $request->boolean('sms_send_immediately');
        $scheduledAt = $validated['sms_scheduled_at'] ?? null;

        return [
            'sms_mode' => $immediate
                ? CareerOpportunity::SMS_IMMEDIATE
                : (filled($scheduledAt) ? CareerOpportunity::SMS_SCHEDULED : CareerOpportunity::SMS_NONE),
            'sms_scheduled_at' => $immediate ? null : $scheduledAt,
        ];
    }

    private function afterSave(CareerOpportunity $opportunity, bool $shouldPublish, bool $publishedNow): string
    {
        $smsNote = '';

        if ($shouldPublish) {
            if ($publishedNow) {
                $this->graduateNotifier->notifyInApp($opportunity);
            }

            $sms = $this->graduateNotifier->dispatchIfDue($opportunity->fresh());
            $smsNote = $this->smsFlash($opportunity->fresh(), $sms);
        }

        if (! $shouldPublish) {
            return 'Career opportunity saved as a draft.';
        }

        $base = $publishedNow
            ? 'Career opportunity published and alumni were notified.'
            : 'Career opportunity updated and published.';

        return trim($base.' '.$smsNote);
    }

    /** @param array{sent: int, skipped: int, delivered: bool} $sms */
    private function smsFlash(CareerOpportunity $opportunity, array $sms): string
    {
        if ($opportunity->sms_mode === CareerOpportunity::SMS_NONE) {
            return '';
        }

        if ($sms['delivered'] && $opportunity->sms_mode === CareerOpportunity::SMS_SCHEDULED && $opportunity->sms_scheduled_at?->isFuture()) {
            return 'SMS scheduled for '.$opportunity->sms_scheduled_at->timezone(config('app.timezone'))->format('M d, Y g:i A').'.';
        }

        if ($sms['delivered'] && $sms['sent'] > 0) {
            $audience = $opportunity->sms_mode === CareerOpportunity::SMS_IMMEDIATE ? 'senior alumni' : 'alumni';
            $note = 'SMS sent to '.$sms['sent'].' '.$audience.'.';

            return $sms['skipped'] > 0
                ? $note.' '.$sms['skipped'].' '.$audience.' had no valid contact number.'
                : $note;
        }

        if ($sms['delivered']) {
            return 'No graduate contact numbers were available for SMS.';
        }

        if (filled($opportunity->sms_last_error)) {
            return 'SMS was not sent: '.$opportunity->sms_last_error;
        }

        if ($opportunity->sms_mode === CareerOpportunity::SMS_SCHEDULED && $opportunity->sms_scheduled_at?->isFuture()) {
            return 'SMS is scheduled for '.$opportunity->sms_scheduled_at->timezone(config('app.timezone'))->format('M d, Y g:i A').'.';
        }

        return 'Graduate SMS could not be sent yet. The system will retry for graduates with contact numbers.';
    }

    private function graduateQuery()
    {
        return User::query()->where(function ($query) {
            $query->where('trainee_status', EnrollmentApplication::LEARNING_GRADUATED)
                ->orWhereHas('enrollmentApplication', fn ($enrollment) => $this->graduateScope($enrollment));
        });
    }

    private function graduateScope($query)
    {
        return $query
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED);
    }
}
