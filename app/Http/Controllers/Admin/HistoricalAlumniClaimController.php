<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AlumniProfile;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Notifications\HistoricalAlumniClaimStatusUpdated;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class HistoricalAlumniClaimController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedStatus = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $statuses = HistoricalAlumniClaim::statuses();

        $query = HistoricalAlumniClaim::query()
            ->with(['user.alumniProfile', 'reviewer', 'onsiteVerifier'])
            ->latest();

        if (array_key_exists($selectedStatus, $statuses)) {
            $query->where('status', $selectedStatus);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_number', 'like', '%'.$search.'%')
                    ->orWhere('certificate_number', 'like', '%'.$search.'%')
                    ->orWhere('tor_reference', 'like', '%'.$search.'%')
                    ->orWhere('historical_batch_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($users) use ($search): void {
                        $users->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        return view('admin.historical-alumni.index', [
            'claims' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'statuses' => $statuses,
            'counts' => HistoricalAlumniClaim::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'totalClaims' => HistoricalAlumniClaim::query()->count(),
        ]);
    }

    public function standing(Request $request): View
    {
        $claims = HistoricalAlumniClaim::query()
            ->with(['user.alumniProfile'])
            ->latest()
            ->get()
            ->map(function (HistoricalAlumniClaim $claim): array {
                $approved = $claim->status === HistoricalAlumniClaim::STATUS_APPROVED;
                $rank = $claim->user?->alumniProfile?->rank ?? AlumniProfile::RANK_JUNIOR;

                return [
                    'name' => $claim->user?->name ?? trim($claim->first_name.' '.$claim->last_name),
                    'email' => $claim->user?->email,
                    'source' => 'Alumni claim',
                    'detail' => $claim->historical_batch_name ?: 'Batch not known',
                    'status' => $claim->statusLabel(),
                    'rank' => $approved ? $rank : null,
                    'promote_url' => $approved && $rank !== AlumniProfile::RANK_SENIOR
                        ? route('admin.historical-alumni.promote', $claim)
                        : null,
                ];
            });

        $graduates = EnrollmentApplication::query()
            ->with(['user.alumniProfile', 'batch'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED)
            ->where(function ($graduates): void {
                $graduates->where('is_historical_record', false)->orWhereNull('is_historical_record');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (EnrollmentApplication $graduate): array {
                $rank = $graduate->user?->alumniProfile?->rank ?? AlumniProfile::RANK_JUNIOR;

                return [
                    'name' => trim($graduate->first_name.' '.$graduate->last_name),
                    'email' => $graduate->email,
                    'source' => 'Training graduate',
                    'detail' => $graduate->batch?->name ?: '—',
                    'status' => 'Graduated',
                    'rank' => $rank,
                    'promote_url' => $rank !== AlumniProfile::RANK_SENIOR
                        ? route('admin.alumni-standing.promote', $graduate)
                        : null,
                ];
            });

        $people = $claims
            ->concat($graduates)
            ->sortBy(fn (array $person) => mb_strtolower($person['name']))
            ->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        return view('admin.alumni-standing.index', [
            'people' => new LengthAwarePaginator(
                $people->forPage($page, $perPage)->values(),
                $people->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            ),
        ]);
    }

    public function show(HistoricalAlumniClaim $historicalAlumniClaim): View
    {
        $historicalAlumniClaim->load(['user.alumniProfile', 'reviewer', 'onsiteVerifier']);

        return view('admin.historical-alumni.show', [
            'claim' => $historicalAlumniClaim,
        ]);
    }

    public function update(Request $request, HistoricalAlumniClaim $historicalAlumniClaim): RedirectResponse
    {
        $validated = $request->validateWithBag('historicalClaimReview', [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'identity_verified' => ['accepted_if:decision,approve'],
            'training_evidence_verified' => ['accepted_if:decision,approve'],
            'archive_record_verified' => ['accepted_if:decision,approve'],
            'admin_notes' => ['required', 'string', 'max:2000'],
        ], [
            '*.accepted_if' => 'Complete all three on-site verification checks before approving this alumni claim.',
        ]);

        $historicalAlumniClaim->load('user');
        if ($validated['decision'] === 'approve' && ! $historicalAlumniClaim->user?->hasVerifiedEmail()) {
            return back()->withErrors([
                'historical_alumni' => 'The claimant must verify their email before MCARE can activate alumni access.',
            ]);
        }

        if ($historicalAlumniClaim->status === HistoricalAlumniClaim::STATUS_APPROVED) {
            return back()->withErrors([
                'historical_alumni' => 'This historical alumni claim is already approved.',
            ]);
        }

        $claim = DB::transaction(function () use ($request, $validated, $historicalAlumniClaim): HistoricalAlumniClaim {
            $claim = HistoricalAlumniClaim::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($historicalAlumniClaim->id);
            $approved = $validated['decision'] === 'approve';

            $claim->forceFill([
                'status' => $approved ? HistoricalAlumniClaim::STATUS_APPROVED : HistoricalAlumniClaim::STATUS_REJECTED,
                'verification_checks' => $approved ? [
                    'identity_verified' => true,
                    'training_evidence_verified' => true,
                    'archive_record_verified' => true,
                ] : null,
                'onsite_verified_at' => $approved ? now() : null,
                'onsite_verified_by_id' => $approved ? $request->user()->id : null,
                'admin_notes' => $validated['admin_notes'],
                'reviewed_at' => now(),
                'reviewed_by_id' => $request->user()->id,
            ])->save();

            if ($approved) {
                $application = EnrollmentApplication::query()->firstOrCreate(
                    ['user_id' => $claim->user_id],
                    [
                        'email' => $claim->user->email,
                        'program' => 'Caregiving NC II',
                        'intake_channel' => 'historical_alumni',
                        'is_historical_record' => true,
                        'training_batch_id' => null,
                        'first_name' => $claim->first_name,
                        'middle_name' => $claim->middle_name,
                        'last_name' => $claim->last_name,
                        'birth_date' => $claim->birth_date,
                        'gender' => $claim->gender,
                        'contact_number' => $claim->contact_number,
                        'schedule_preference' => $claim->training_schedule,
                        'street' => $claim->street,
                        'barangay' => $claim->barangay,
                        'city' => $claim->city,
                        'province' => $claim->province,
                        'region' => $claim->region,
                        'zip_code' => $claim->zip_code,
                        'educational_attainment' => $claim->educational_attainment,
                        'school_name' => $claim->school_name,
                        'year_graduated' => $claim->education_year_graduated,
                        'privacy_consent' => true,
                        'date_accomplished' => $claim->created_at->toDateString(),
                        'status' => EnrollmentApplication::STATUS_APPROVED,
                        'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
                        'learning_status_notes' => 'Historical graduate verified from original COTC/TOR and MCARE archive records.',
                        'learning_status_changed_at' => now(),
                        'learning_status_changed_by_id' => $request->user()->id,
                        'admin_notes' => $validated['admin_notes'],
                        'reviewed_at' => now(),
                        'reviewed_by_id' => $request->user()->id,
                    ],
                );

                // A pre-existing enrollment cannot be silently converted into a
                // historical graduate record through this claim workflow.
                abort_unless($application->wasRecentlyCreated || $application->is_historical_record, 409);

                $claim->user->forceFill([
                    'role' => 'trainee',
                    'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
                ])->save();

                AlumniProfile::query()->firstOrCreate(
                    ['user_id' => $claim->user_id],
                    [
                        'is_available_for_duty' => false,
                        'rank' => AlumniProfile::RANK_JUNIOR,
                    ],
                );
            } else {
                $claim->user->forceFill([
                    'applicant_status' => 'historical_claim_rejected',
                ])->save();
            }

            return $claim->fresh(['user']);
        });

        AdminActivityLog::record($request->user(), 'historical-alumni.claim.reviewed', $claim, [
            'decision' => $validated['decision'],
            'claimant_email' => $claim->user->email,
        ]);

        try {
            $claim->user->notify(new HistoricalAlumniClaimStatusUpdated($claim));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('admin.historical-alumni.show', $claim)
            ->with('saved', $validated['decision'] === 'approve'
                ? "Historical alumni access activated for {$claim->user->name}."
                : "Historical alumni claim for {$claim->user->name} was returned for follow-up.");
    }

    public function promote(Request $request, HistoricalAlumniClaim $historicalAlumniClaim): RedirectResponse
    {
        abort_unless($historicalAlumniClaim->status === HistoricalAlumniClaim::STATUS_APPROVED, 422);
        $historicalAlumniClaim->load('user');

        $profile = $this->promoteProfile($request, $historicalAlumniClaim->user_id);
        $name = $historicalAlumniClaim->user->name;

        AdminActivityLog::record($request->user(), 'historical-alumni.rank.promoted', $historicalAlumniClaim, [
            'claimant_email' => $historicalAlumniClaim->user->email,
            'rank' => $profile->rank,
        ]);

        return back()->with('saved', $profile->wasChanged('rank')
            ? "{$name} is now a senior alumni."
            : "{$name} is already a senior alumni.");
    }

    public function promoteGraduate(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        abort_unless(
            $enrollmentApplication->status === EnrollmentApplication::STATUS_APPROVED
            && $enrollmentApplication->learning_status === EnrollmentApplication::LEARNING_GRADUATED
            && ! $enrollmentApplication->is_historical_record,
            422,
        );

        $profile = $this->promoteProfile($request, (int) $enrollmentApplication->user_id);
        $name = trim($enrollmentApplication->first_name.' '.$enrollmentApplication->last_name);

        AdminActivityLog::record($request->user(), 'graduate.rank.promoted', $enrollmentApplication, [
            'rank' => $profile->rank,
        ]);

        return back()->with('saved', "{$name} is now a senior alumni.");
    }

    private function promoteProfile(Request $request, int $userId): AlumniProfile
    {
        $profile = AlumniProfile::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'is_available_for_duty' => false,
                'rank' => AlumniProfile::RANK_JUNIOR,
            ],
        );

        if ($profile->isSenior()) {
            return $profile;
        }

        $profile->forceFill([
            'rank' => AlumniProfile::RANK_SENIOR,
            'rank_promoted_at' => now(),
            'rank_promoted_by_id' => $request->user()->id,
        ])->save();

        return $profile;
    }

    public function evidence(Request $request, HistoricalAlumniClaim $historicalAlumniClaim): BinaryFileResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', Rule::in([1, 2])],
            'disposition' => ['nullable', Rule::in(['inline', 'attachment'])],
        ]);
        $page = (int) ($validated['page'] ?? 1);
        $path = $page === 2
            ? $historicalAlumniClaim->evidence_document_page_2_path
            : $historicalAlumniClaim->evidence_document_path;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $disposition = ($validated['disposition'] ?? 'inline') === 'attachment'
            ? HeaderUtils::DISPOSITION_ATTACHMENT
            : HeaderUtils::DISPOSITION_INLINE;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'historical-alumni-evidence-'.$historicalAlumniClaim->id.'-page-'.$page.($extension ? '.'.$extension : '');

        AdminActivityLog::record($request->user(), 'historical-alumni.evidence.opened', $historicalAlumniClaim, [
            'page' => $page,
            'disposition' => $disposition,
        ]);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
