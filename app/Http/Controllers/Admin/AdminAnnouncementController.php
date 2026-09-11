<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminAnnouncement;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\AnnouncementDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementDeliveryService $announcementDelivery,
    ) {}

    public function index(Request $request): View
    {
        $announcements = AdminAnnouncement::query()
            ->with(['author', 'batch', 'targetUser'])
            ->latest('posted_at')
            ->paginate(15);

        $batches = TrainingBatch::query()
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();

        $approvedTrainees = User::query()
            ->whereHas('enrollmentApplication', fn ($q) => $q->where('status', EnrollmentApplication::STATUS_APPROVED))
            ->with('enrollmentApplication.batch')
            ->orderBy('name')
            ->get();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'batches' => $batches,
            'approvedTrainees' => $approvedTrainees,
            'kinds' => AdminAnnouncement::kinds(),
            'targetTypes' => AdminAnnouncement::targetTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'kind' => ['required', Rule::in(array_keys(AdminAnnouncement::kinds()))],
            'target_type' => ['required', Rule::in(array_keys(AdminAnnouncement::targetTypes()))],
            'training_batch_id' => ['nullable', 'required_if:target_type,batch', 'exists:training_batches,id'],
            'target_user_id' => ['nullable', 'required_if:target_type,user', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $announcement = AdminAnnouncement::create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'kind' => $validated['kind'],
            'target_type' => $validated['target_type'],
            'training_batch_id' => $validated['target_type'] === 'batch' ? $validated['training_batch_id'] : null,
            'target_user_id' => $validated['target_type'] === 'user' ? $validated['target_user_id'] : null,
            'due_date' => $validated['due_date'] ?? null,
            'send_email' => $request->boolean('send_email'),
            'is_published' => true,
            'posted_at' => now(),
        ]);

        // Dispatch notifications & emails to recipients
        $recipients = match ($announcement->target_type) {
            AdminAnnouncement::TARGET_USER => User::query()->whereKey($announcement->target_user_id)->get(),
            AdminAnnouncement::TARGET_BATCH => User::query()
                ->where('role', 'trainee')
                ->whereHas('enrollmentApplication', fn ($q) => $q
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->where('training_batch_id', $announcement->training_batch_id))
                ->distinct()
                ->get(),
            default => User::query()
                ->where('role', 'trainee')
                ->whereHas('enrollmentApplication', fn ($q) => $q->where('status', EnrollmentApplication::STATUS_APPROVED))
                ->distinct()
                ->get(),
        };

        if ($recipients->isNotEmpty()) {
            $this->announcementDelivery->deliverAdminAnnouncement($announcement, $recipients);
        }

        AdminActivityLog::record($request->user(), 'admin.announcement.published', $announcement, [
            'target_type' => $announcement->target_type,
            'recipients_count' => $recipients->count(),
            'send_email' => $announcement->send_email,
            'due_date' => $announcement->due_date?->toDateString(),
        ]);

        $emailNotice = $announcement->send_email ? ' and emailed' : '';

        return back()->with('saved', "Announcement published{$emailNotice} to {$recipients->count()} recipient(s).");
    }

    public function destroy(Request $request, AdminAnnouncement $announcement): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'admin.announcement.deleted', $announcement, [
            'title' => $announcement->title,
        ]);

        $announcement->delete();

        return back()->with('saved', 'Announcement removed.');
    }
}
