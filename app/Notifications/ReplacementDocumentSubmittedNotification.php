<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplacementDocumentSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public EnrollmentApplication $application,
        public string $documentLabel,
        public ?string $applicationNumber = null,
    ) {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Replacement document received',
            'message' => 'Your replacement '.$this->documentLabel.' was uploaded successfully and is currently waiting for registrar review.',
            'url' => route('applications.status', ['application_number' => $this->applicationNumber ?? $this->application->enrollment_number]),
            'icon' => 'file-check',
            'enrollment_application_id' => $this->application->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = ($notifiable instanceof \App\Models\User ? $notifiable->name : null)
            ?: trim(($this->application->first_name ?? '').' '.($this->application->last_name ?? ''))
            ?: 'Applicant';

        $number = $this->applicationNumber ?? $this->application->enrollment_number;
        $statusUrl = route('applications.status', ['application_number' => $number]);

        return (new MailMessage)
            ->subject('MCARE: Replacement '.$this->documentLabel.' Received')
            ->view('mail.enrollment-status', [
                'title' => 'MCARE: Replacement '.$this->documentLabel.' received',
                'heading' => 'Replacement Document Received',
                'recipientName' => $recipientName,
                'intro' => 'We have received your replacement file for '.$this->documentLabel.'. Your document has been placed in the registrar review queue for evaluation.',
                'enrollmentNumber' => $number,
                'adminNotes' => 'The registrar will inspect your updated document. You can track your verification progress at any time using the link below.',
                'actionLabel' => 'Check Application Status',
                'actionUrl' => $statusUrl,
                'secondaryActionLabel' => null,
                'secondaryActionUrl' => null,
                'closing' => 'Thank you for updating your requirements promptly.',
            ]);
    }
}
