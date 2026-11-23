<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantDocumentOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $otpCode,
        public readonly string $applicationNumber,
        public readonly ?string $recipientName = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $name = ($notifiable instanceof User ? $notifiable->name : null)
            ?: $this->recipientName
            ?: 'Applicant';

        return (new MailMessage)
            ->subject('MCARE: Your Document Verification Code is '.$this->otpCode)
            ->view('mail.applicant-document-otp', [
                'recipientName' => $name,
                'applicationNumber' => $this->applicationNumber,
                'otpCode' => $this->otpCode,
            ]);
    }
}
