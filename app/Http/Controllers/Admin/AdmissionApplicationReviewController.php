<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdmissionApplicationReviewedMail;
use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\PublicSiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdmissionApplicationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedStatus = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $statuses = AdmissionApplication::statuses();

        $query = AdmissionApplication::query()->with('enrollment')->latest();

        if (array_key_exists($selectedStatus, $statuses)) {
            $query->where('status', $selectedStatus);
        } else {
            // Default view hides already-approved applicants. They stay reachable
            // through the "Approved" quick-filter card above the table.
            $query->where('status', '!=', AdmissionApplication::STATUS_APPROVED);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('application_number', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_number', 'like', '%'.$search.'%');
            });
        }

        $counts = AdmissionApplication::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.applications.index', [
            'admissions' => $query->paginate(12)->withQueryString(),
            'counts' => $counts,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'statuses' => $statuses,
            'totalApplications' => AdmissionApplication::query()->count(),
            'unusedApprovedExpirySummary' => PublicSiteSetting::current()->unusedApprovedExpirySummary(),
        ]);
    }

    public function show(AdmissionApplication $admissionApplication): View
    {
        $admissionApplication->load(['reviewer', 'enrollment', 'trainingProgram']);

        return view('admin.applications.show', [
            'admission' => $admissionApplication,
            'statuses' => AdmissionApplication::statuses(),
        ]);
    }

    public function update(Request $request, AdmissionApplication $admissionApplication): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([AdmissionApplication::STATUS_APPROVED, AdmissionApplication::STATUS_DENIED])],
            'admin_notes' => [
                $request->input('status') === AdmissionApplication::STATUS_DENIED ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'admin_notes.required' => 'Add a short reason before denying this application.',
        ]);

        if ($admissionApplication->enrollment()->exists() && $validated['status'] === AdmissionApplication::STATUS_DENIED) {
            return back()->withErrors([
                'status' => 'This application number is already linked to a submitted enrollment and cannot be denied.',
            ]);
        }

        $admissionApplication->forceFill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()?->id,
        ])->save();

        AdminActivityLog::record($request->user(), 'admission.reviewed', $admissionApplication, [
            'application_number' => $admissionApplication->application_number,
            'status' => $admissionApplication->status,
        ]);

        try {
            Mail::to($admissionApplication->email)->send(new AdmissionApplicationReviewedMail($admissionApplication));
        } catch (Throwable $exception) {
            report($exception);
        }

        $notice = $admissionApplication->isApproved()
            ? 'Application '.$admissionApplication->application_number.' is approved. The applicant can now enroll with this number.'
            : 'Application '.$admissionApplication->application_number.' was denied.';

        return redirect()
            ->route('admin.applications.show', $admissionApplication)
            ->with('saved', $notice);
    }

    public function destroy(Request $request, AdmissionApplication $admissionApplication): RedirectResponse
    {
        if ($admissionApplication->enrollment()->exists()) {
            return back()->withErrors([
                'application' => 'Application '.$admissionApplication->application_number.' is linked to a submitted enrollment and cannot be deleted.',
            ]);
        }

        $number = $admissionApplication->application_number;

        AdminActivityLog::record($request->user(), 'admission.deleted', $admissionApplication, [
            'application_number' => $number,
            'status' => $admissionApplication->status,
            'email' => $admissionApplication->email,
        ]);

        $admissionApplication->delete();

        return redirect()
            ->route('admin.applications.index')
            ->with('saved', 'Application '.$number.' was deleted.');
    }
}
