<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminContactMessage;
use App\Notifications\AdminOperationsNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $allowedStatuses = array_keys(AdminContactMessage::statuses());

        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $messages = AdminContactMessage::query()
            ->with(['user', 'reviewer'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [AdminContactMessage::STATUS_PENDING])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.contact-messages.index', [
            'messages' => $messages,
            'statuses' => AdminContactMessage::statuses(),
            'currentStatus' => $status,
            'pendingCount' => AdminContactMessage::query()
                ->where('status', AdminContactMessage::STATUS_PENDING)
                ->count(),
        ]);
    }

    public function update(Request $request, AdminContactMessage $contactMessage): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(AdminContactMessage::statuses()))],
            'admin_reply' => ['nullable', 'string', 'max:2000', 'not_regex:/[<>]/u'],
        ]);

        $contactMessage->fill([
            'status' => $validated['status'],
            'admin_reply' => $validated['admin_reply'] ?? null,
        ]);

        if ($validated['status'] === AdminContactMessage::STATUS_PENDING) {
            $contactMessage->reviewed_at = null;
            $contactMessage->reviewed_by_id = null;
        } else {
            $contactMessage->reviewed_at = $contactMessage->reviewed_at ?: now();
            $contactMessage->reviewed_by_id = $request->user()->id;
        }

        $contactMessage->save();
        $contactMessage->loadMissing('user');

        AdminActivityLog::record($request->user(), 'admin.contact.updated', $contactMessage, [
            'status' => $contactMessage->status,
        ]);

        $this->notifySender($contactMessage);

        return back()->with('saved', 'Contact message updated.');
    }

    public function destroy(Request $request, AdminContactMessage $contactMessage): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'admin.contact.deleted', $contactMessage, [
            'subject' => $contactMessage->subject,
            'sender' => $contactMessage->user?->name,
        ]);

        $contactMessage->delete();

        return back()->with('saved', 'Contact message removed.');
    }

    private function notifySender(AdminContactMessage $contactMessage): void
    {
        $sender = $contactMessage->user;

        if (! $sender) {
            return;
        }

        $replyChanged = $contactMessage->wasChanged('admin_reply') && filled($contactMessage->admin_reply);
        $statusChanged = $contactMessage->wasChanged('status')
            && $contactMessage->status !== AdminContactMessage::STATUS_PENDING;

        if (! $replyChanged && ! $statusChanged) {
            return;
        }

        try {
            $sender->notify(new AdminOperationsNotification(
                title: $replyChanged ? 'Admin replied to your message' : 'Your message was updated',
                message: $replyChanged
                    ? 'MCARE administration replied to "'.$contactMessage->subject.'".'
                    : 'Your message "'.$contactMessage->subject.'" was marked '.$contactMessage->statusLabel().'.',
                url: route('account.help'),
                icon: 'message-circle',
                event: 'account.contact.updated',
                context: [
                    'contact_message_id' => $contactMessage->id,
                ],
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
