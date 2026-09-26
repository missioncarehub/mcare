<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CareerInquiry;
use App\Models\CareerOpportunity;
use App\Services\AdminOperationsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniCareerHubController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->alumniProfile()->firstOrCreate([], [
            'is_available_for_duty' => false,
        ]);

        return view('trainee.career-hub', [
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->orderBy('estimated_start_date')
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
            'alumniProfile' => $profile,
            'contactedJobIds' => CareerInquiry::query()
                ->where('user_id', $request->user()->id)
                ->pluck('career_opportunity_id')
                ->all(),
        ]);
    }

    public function achievements(Request $request): View
    {
        $achievements = CareerInquiry::query()
            ->with('opportunity')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return view('trainee.achievements', [
            'achievements' => $achievements,
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

    public function contact(
        Request $request,
        CareerOpportunity $careerOpportunity,
        AdminOperationsNotifier $adminNotifier,
    ): RedirectResponse {
        abort_unless($careerOpportunity->isVisibleToAlumni(), 404);

        $graduate = $request->user()->loadMissing('enrollmentApplication');
        $safeText = ['not_regex:/[<>]/u'];

        $validated = $request->validateWithBag('careerContact', [
            'name' => ['required', 'string', 'max:120', ...$safeText],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'message' => ['required', 'string', 'max:1000', ...$safeText],
        ]);

        if (CareerInquiry::query()
            ->where('user_id', $graduate->id)
            ->where('career_opportunity_id', $careerOpportunity->id)
            ->exists()) {
            return back()->with([
                'saved' => 'MCARE administration already received your inquiry for this career.',
                'saved_icon' => 'circle-check',
            ]);
        }

        $inquiry = CareerInquiry::create([
            ...$validated,
            'career_opportunity_id' => $careerOpportunity->id,
            'user_id' => $graduate->id,
            'status' => CareerInquiry::STATUS_PENDING,
        ]);

        $adminNotifier->notify(
            title: 'Career Hub inquiry',
            message: $inquiry->name.' submitted an inquiry for '.$careerOpportunity->listingTitle().'.',
            url: route('admin.learning.alumni-jobs'),
            icon: 'briefcase',
            event: 'career.inquiry.received',
            context: [
                'opportunity_id' => $careerOpportunity->id,
                'inquiry_id' => $inquiry->id,
                'graduate_id' => $graduate->id,
            ],
        );

        AdminActivityLog::record($graduate, 'career.inquiry.created', $inquiry, [
            'opportunity_id' => $careerOpportunity->id,
        ]);

        return back()->with([
            'saved' => 'Your inquiry was sent to MCARE administration.',
            'saved_icon' => 'circle-check',
        ]);
    }
}
