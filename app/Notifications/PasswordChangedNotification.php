<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $changedAt,
        public readonly ?string $ipAddress = null,
        public readonly string $source = 'Account Settings',
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = ($notifiable instanceof User ? $notifiable->name : null) ?: 'MCARE Account Holder';

        return (new MailMessage)
            ->subject('Your MCARE password was changed')
            ->view('mail.password-changed', [
                'recipientName' => $recipientName,
                'changedAt' => $this->changedAt,
                'ipAddress' => $this->ipAddress,
                'source' => $this->source,
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Password changed',
            'message' => 'Your account password was successfully changed on '.$this->changedAt.' ('.$this->source.').',
            'url' => route('account.settings'),
            'icon' => 'lock',
        ];
    }
}
