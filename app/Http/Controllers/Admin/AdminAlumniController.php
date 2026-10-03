<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlumniProfile;
use App\Models\CareerInquiry;
use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAlumniController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $availability = (string) $request->query('availability', '');

        $baseQuery = $this->graduateQuery();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'available' => (clone $baseQuery)->whereHas('alumniProfile', fn ($query) => $query->where('is_available_for_duty', true))->count(),
            'senior' => (clone $baseQuery)->whereHas('alumniProfile', fn ($query) => $query->where('rank', AlumniProfile::RANK_SENIOR))->count(),
        ];

        $alumni = (clone $baseQuery)
            ->with(['alumniProfile', 'enrollmentApplication'])
            ->withCount([
                'careerInquiries as applications_count',
                'careerInquiries as awarded_careers_count' => fn ($query) => $query
                    ->where('status', CareerInquiry::STATUS_APPROVED)
                    ->where('certificate_status', CareerInquiry::CERT_APPROVED),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhereHas('enrollmentApplication', fn ($enrollment) => $enrollment
                            ->where('contact_number', 'like', "%{$search}%"));
                });
            })
            ->when($availability === 'available', fn ($query) => $query->whereHas('alumniProfile', fn ($profile) => $profile->where('is_available_for_duty', true)))
            ->when($availability === 'unavailable', fn ($query) => $query->where(function ($inner) {
                $inner->whereDoesntHave('alumniProfile')
                    ->orWhereHas('alumniProfile', fn ($profile) => $profile->where('is_available_for_duty', false));
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.alumni.index', [
            'alumni' => $alumni,
            'search' => $search,
            'availability' => $availability,
            'stats' => $stats,
        ]);
    }

    public function show(User $user): View
    {
        abort_unless($user->isGraduate(), 404);

        $user->load([
            'alumniProfile',
            'enrollmentApplication.batch',
            'historicalAlumniClaim',
            'careerInquiries.opportunity',
        ]);

        $enrollment = $user->enrollmentApplication;
        $profile = $user->alumniProfile;
        $applications = $user->careerInquiries->sortByDesc('created_at')->values();

        return view('admin.alumni.show', [
            'alumni' => $user,
            'enrollment' => $enrollment,
            'profile' => $profile,
            'historicalClaim' => $user->historicalAlumniClaim,
            'applications' => $applications,
            'awardedCareers' => $applications->filter(fn (CareerInquiry $inquiry) => $inquiry->isCareerAwarded())->count(),
        ]);
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
