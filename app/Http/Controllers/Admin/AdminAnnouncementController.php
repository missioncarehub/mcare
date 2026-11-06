<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminAnnouncement;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\AnnouncementDeliveryService;
use App\Services\SemaphoreSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'send_sms' => ['nullable', 'boolean'],
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
            'send_sms' => $request->boolean('send_sms'),
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
            $recipients->load('enrollmentApplication');
            $this->announcementDelivery->deliverAdminAnnouncement($announcement, $recipients);
        }

        $smsDelivery = $announcement->send_sms
            ? $this->sendAnnouncementSms($announcement, $recipients)
            : ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'recipients' => []];

        if ($announcement->send_sms) {
            $announcement->forceFill(['sms_delivery' => $smsDelivery])->save();
        }

        AdminActivityLog::record($request->user(), 'admin.announcement.published', $announcement, [
            'target_type' => $announcement->target_type,
            'recipients_count' => $recipients->count(),
            'send_email' => $announcement->send_email,
            'send_sms' => $announcement->send_sms,
            'sms_delivery' => $smsDelivery,
            'due_date' => $announcement->due_date?->toDateString(),
        ]);

        $channels = ['in-app'];
        if ($announcement->send_email) {
            $channels[] = 'email';
        }
        if ($announcement->send_sms) {
            $channels[] = 'SMS';
        }

        $message = 'Announcement published by '.implode(', ', $channels).' to '.$recipients->count().' recipient(s).';
        if ($announcement->send_sms) {
            $message .= ' SMS sent '.$smsDelivery['sent'].', failed '.$smsDelivery['failed'].', skipped '.$smsDelivery['skipped'].'.';
        }

        return back()->with('saved', $message);
    }

    public function destroy(Request $request, AdminAnnouncement $announcement): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'admin.announcement.deleted', $announcement, [
            'title' => $announcement->title,
        ]);

        $announcement->delete();

        return back()->with('saved', 'Announcement removed.');
    }

    /**
     * @param  iterable<User>  $recipients
     * @return array{sent: int, failed: int, skipped: int, recipients: list<array{user_id: int, status: string, error: ?string}>}
     */
    private function sendAnnouncementSms(AdminAnnouncement $announcement, iterable $recipients): array
    {
        $sms = app(SemaphoreSmsService::class);
        $body = Str::limit(trim($announcement->title.': '.$announcement->message), 480, '');
        $delivery = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'recipients' => []];

        foreach ($recipients as $user) {
            $number = $user->contact_number ?: $user->enrollmentApplication?->contact_number;
            $result = $sms->sendOne($number, $body);
            $status = $result['sent'] ? 'sent' : ($result['skipped'] ? 'skipped' : 'failed');
            $delivery[$status]++;
            $delivery['recipients'][] = [
                'user_id' => $user->id,
                'status' => $status,
                'error' => $result['error'],
            ];
        }

        return $delivery;
    }
}
