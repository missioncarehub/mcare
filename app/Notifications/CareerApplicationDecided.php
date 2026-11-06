<?php

namespace App\Notifications;

use App\Models\CareerInquiry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareerApplicationDecided extends Notification
{
    public function __construct(
        public CareerInquiry $inquiry,
        public string $decision,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $career = $this->inquiry->opportunity?->listingTitle() ?? 'the career';
        $selected = $this->decision === 'approved';

        return [
            'title' => $selected ? 'Career application approved' : 'Career application not selected',
            'message' => $selected
                ? 'MCARE approved your application for '.$career.'. Upload your placement certificate in Career history.'
                : 'The opening for '.$career.' was filled. Your application was not selected.',
            'url' => $selected ? route('trainee.achievements') : route('trainee.applications'),
            'icon' => $selected ? 'circle-check' : 'briefcase',
            'inquiry_id' => $this->inquiry->id,
            'opportunity_id' => $this->inquiry->career_opportunity_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $career = $this->inquiry->opportunity?->listingTitle() ?? 'the career';
        $selected = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject($selected ? 'MCARE career application approved' : 'MCARE career application update')
            ->line($selected
                ? 'Your application for '.$career.' was approved. Upload your placement certificate so MCARE can review it.'
                : 'The opening for '.$career.' was filled. Your application was not selected.');

        return $mail->action(
            $selected ? 'Open career history' : 'View your applications',
            $selected ? route('trainee.achievements') : route('trainee.applications'),
        );
    }
}
