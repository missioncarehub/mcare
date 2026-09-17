<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Path: app/Notifications/EnrollmentDocumentsReviseRequestedNotification.php
 * Label: Applicant email notifying that submitted documents need revision.
 *
 * Sent from the admin document-review page whenever the administrator marks
 * one or more enrollment documents as "Needs replacement" and clicks
 * "Request revisions". The email includes the direct link to the applicant's
 * enrollment/documents page so the applicant can immediately re-upload.
 */
class EnrollmentDocumentsReviseRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  list<string>  $documentsNeedingRevision  Human labels of documents flagged for replacement.
     */
    public function __construct(
        public EnrollmentApplication $application,
        public array $documentsNeedingRevision,
        public ?string $remark = null,
    ) {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Enrollment documents need revision',
            'message' => 'MCARE administration asked you to revise '
                .count($this->documentsNeedingRevision).' enrollment '
                .\Illuminate\Support\Str::plural('document', count($this->documentsNeedingRevision))
                .'. Open the enrollment page to re-upload the corrected files.',
            'url' => route('enrollment.create'),
            'icon' => 'clipboard-list',
            'enrollment_application_id' => $this->application->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $notifiable->name ?: 'Applicant';
        $listLine = $this->documentsNeedingRevision === []
            ? 'One or more of your submitted enrollment documents need to be revised.'
            : 'The following documents need to be re-uploaded: '.implode(', ', $this->documentsNeedingRevision).'.';

        $intro = 'MCARE administration reviewed your enrollment documents and requires revisions before your enrollment can proceed. '
            .$listLine;

        return (new MailMessage)
            ->subject('Please revise your MCARE enrollment documents')
            ->view('mail.enrollment-status', [
                'title' => 'MCARE enrollment: documents need revision',
                'heading' => 'Please revise your enrollment documents',
                'recipientName' => $recipientName,
                'intro' => $intro,
                'enrollmentNumber' => $this->application->enrollment_number,
                'adminNotes' => $this->remark,
                'actionLabel' => 'Open my enrollment page',
                'actionUrl' => route('enrollment.create'),
                'secondaryActionLabel' => null,
                'secondaryActionUrl' => null,
                'closing' => 'Once you re-upload the corrected files, MCARE administration will resume the review of your enrollment.',
            ]);
    }
}
