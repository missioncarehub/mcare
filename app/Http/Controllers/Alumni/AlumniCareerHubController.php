<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CareerInquiry;
use App\Models\CareerOpportunity;
use App\Services\AdminOperationsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AlumniCareerHubController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->alumniProfile()->firstOrCreate([], [
            'is_available_for_duty' => false,
        ]);

        $applications = CareerInquiry::query()
            ->where('user_id', $request->user()->id)
            ->pluck('status', 'career_opportunity_id')
            ->all();

        return view('trainee.career-hub', [
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->orderBy('estimated_start_date')
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
            'alumniProfile' => $profile,
            'applicationStatuses' => $applications,
        ]);
    }

    public function applications(Request $request): View
    {
        $applications = CareerInquiry::query()
            ->with('opportunity')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return view('trainee.applications', [
            'applications' => $applications,
        ]);
    }

    public function achievements(Request $request): View
    {
        $achievements = CareerInquiry::query()
            ->with('opportunity')
            ->where('user_id', $request->user()->id)
            ->where('status', CareerInquiry::STATUS_APPROVED)
            ->latest('reviewed_at')
            ->latest()
            ->paginate(12);

        return view('trainee.achievements', [
            'achievements' => $achievements,
        ]);
    }

    public function uploadPlacementCertificate(
        Request $request,
        CareerInquiry $careerInquiry,
        AdminOperationsNotifier $adminNotifier,
    ): RedirectResponse {
        abort_unless($careerInquiry->user_id === $request->user()->id, 403);
        abort_unless($careerInquiry->needsCertificateUpload(), 404);

        $validated = $request->validate([
            'placement_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $oldPath = data_get($careerInquiry->placement_certificate, 'path');
        if (is_string($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        $file = $request->file('placement_certificate');
        $storedPath = $file->store('career-placement-certificates/'.$request->user()->id, 'local');

        $careerInquiry->update([
            'placement_certificate' => [
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
            'certificate_status' => CareerInquiry::CERT_PENDING_REVIEW,
            'certificate_admin_notes' => null,
            'certificate_reviewed_at' => null,
            'certificate_reviewed_by_id' => null,
        ]);

        $careerInquiry->loadMissing('opportunity');

        $adminNotifier->notify(
            title: 'Placement certificate submitted',
            message: $careerInquiry->name.' uploaded a placement certificate for '.$careerInquiry->opportunity?->listingTitle().'.',
            url: route('admin.learning.alumni-jobs'),
            icon: 'award',
            event: 'career.certificate.submitted',
            context: [
                'inquiry_id' => $careerInquiry->id,
                'graduate_id' => $careerInquiry->user_id,
            ],
        );

        AdminActivityLog::record($request->user(), 'career.certificate.uploaded', $careerInquiry, [
            'opportunity_id' => $careerInquiry->career_opportunity_id,
        ]);

        return back()->with([
            'saved' => 'Your placement certificate was submitted. MCARE administration will review it.',
            'saved_icon' => 'circle-check',
        ]);
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_available_for_duty' => ['required', 'boolean'],
        ]);

        $available = (bool) $validated['is_available_for_duty'];
        $profile = $request->user()->alumniProfile()->updateOrCreate([], [
            'is_available_for_duty' => $available,
            'availability_updated_at' => now(),
        ]);

        AdminActivityLog::record($request->user(), 'alumni.availability.updated', $profile, [
            'is_available_for_duty' => $available,
        ]);

        return back()->with([
            'saved' => $available
                ? 'You are now marked Available for Duty.'
                : 'Your availability is now set to unavailable.',
            'saved_icon' => $available ? 'circle-check' : 'circle-minus',
            'saved_tone' => $available ? 'available' : 'unavailable',
        ]);
    }

    public function showApplyForm(Request $request, CareerOpportunity $careerOpportunity): View
    {
        abort_unless($careerOpportunity->isVisibleToAlumni(), 404);

        $existing = CareerInquiry::query()
            ->where('user_id', $request->user()->id)
            ->where('career_opportunity_id', $careerOpportunity->id)
            ->first();

        $graduate = $request->user()->loadMissing('enrollmentApplication');

        return view('trainee.career-apply', [
            'job' => $careerOpportunity,
            'existingApplication' => $existing,
            'graduate' => $graduate,
        ]);
    }

    public function apply(
        Request $request,
        CareerOpportunity $careerOpportunity,
        AdminOperationsNotifier $adminNotifier,
    ): RedirectResponse {
        abort_unless($careerOpportunity->isVisibleToAlumni(), 404);

        $graduate = $request->user()->loadMissing('enrollmentApplication');
        $safeText = ['not_regex:/[<>]/u'];

        $validated = $request->validateWithBag('careerApply', [
            'name' => ['required', 'string', 'max:120', ...$safeText],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'message' => ['required', 'string', 'max:1000', ...$safeText],
            'credentials' => ['nullable', 'array', 'max:5'],
            'credentials.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (CareerInquiry::query()
            ->where('user_id', $graduate->id)
            ->where('career_opportunity_id', $careerOpportunity->id)
            ->exists()) {
            return redirect()->route('trainee.applications')->with([
                'saved' => 'You already applied for this career. MCARE administration will review your application.',
                'saved_icon' => 'circle-check',
            ]);
        }

        $credentialPaths = [];
        if ($request->hasFile('credentials')) {
            foreach ($request->file('credentials') as $file) {
                $storedPath = $file->store("career-credentials/{$graduate->id}", 'local');
                $credentialPaths[] = [
                    'path' => $storedPath,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $inquiry = CareerInquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'],
            'message' => $validated['message'],
            'credential_paths' => $credentialPaths ?: null,
            'career_opportunity_id' => $careerOpportunity->id,
            'user_id' => $graduate->id,
            'status' => CareerInquiry::STATUS_PENDING,
        ]);

        $adminNotifier->notify(
            title: 'Career application received',
            message: $inquiry->name.' applied for '.$careerOpportunity->listingTitle().'.',
            url: route('admin.learning.alumni-jobs'),
            icon: 'briefcase',
            event: 'career.application.received',
            context: [
                'opportunity_id' => $careerOpportunity->id,
                'inquiry_id' => $inquiry->id,
                'graduate_id' => $graduate->id,
            ],
        );

        AdminActivityLog::record($graduate, 'career.application.created', $inquiry, [
            'opportunity_id' => $careerOpportunity->id,
        ]);

        return redirect()->route('trainee.applications')->with([
            'saved' => 'Your application was submitted. MCARE administration will review it.',
            'saved_icon' => 'circle-check',
        ]);
    }

    public function credential(Request $request, CareerInquiry $careerInquiry, int $index)
    {
        $isOwner = $careerInquiry->user_id === $request->user()->id;
        $isAdmin = $request->user()->role === 'admin';
        abort_unless($isOwner || $isAdmin, 403);

        $file = data_get($careerInquiry->credential_paths, $index);
        $path = data_get($file, 'path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $name = data_get($file, 'name', 'credential');

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function placementCertificate(Request $request, CareerInquiry $careerInquiry)
    {
        $isOwner = $careerInquiry->user_id === $request->user()->id;
        $isAdmin = $request->user()->role === 'admin';
        abort_unless($isOwner || $isAdmin, 403);

        $path = data_get($careerInquiry->placement_certificate, 'path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $name = data_get($careerInquiry->placement_certificate, 'name', 'placement-certificate');

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @deprecated Use apply() instead */
    public function contact(
        Request $request,
        CareerOpportunity $careerOpportunity,
        AdminOperationsNotifier $adminNotifier,
    ): RedirectResponse {
        return $this->apply($request, $careerOpportunity, $adminNotifier);
    }
}
