<?php

namespace App\Notifications;

use App\Models\AdminAnnouncement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAnnouncementNotification extends Notification
{
    public function __construct(
        public AdminAnnouncement $announcement,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->announcement->send_email && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $isPayment = $this->announcement->kind === AdminAnnouncement::KIND_REMINDER;
        $isGraduate = method_exists($notifiable, 'isGraduate') && $notifiable->isGraduate();
        $streamUrl = $isGraduate && \Illuminate\Support\Facades\Route::has('trainee.career-hub')
            ? route('trainee.career-hub')
            : route('trainee.stream');

        return [
            'title' => $this->announcement->title,
            'message' => str($this->announcement->message)->limit(160)->toString(),
            'kind' => $this->announcement->kind,
            'due_date' => $this->announcement->due_date?->format('M d, Y'),
            'url' => $isPayment ? route('trainee.payments') : $streamUrl,
            'icon' => $isPayment ? 'credit-card' : 'bell',
            'announcement_id' => $this->announcement->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('MCARE Notice: '.$this->announcement->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->announcement->message);

        if ($this->announcement->due_date) {
            $mail->line('Important Due Date: '.$this->announcement->due_date->format('F d, Y'));
        }

        $url = $this->announcement->kind === AdminAnnouncement::KIND_REMINDER
            ? route('trainee.payments')
            : route('trainee.stream');

        return $mail
            ->action('Open MCARE Portal', $url)
            ->line('Please sign in to your MCARE account to review your record and requirements.')
            ->salutation('MCARE Training Center Administration');
    }
}
