<?php

namespace App\Notifications;

use App\Models\CareerOpportunity;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareerOpportunityPublished extends Notification
{
    public function __construct(
        public CareerOpportunity $opportunity,
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
        return [
            'title' => 'New career available',
            'message' => $this->opportunity->listingTitle().' · Estimated start '.$this->opportunity->estimated_start_date?->format('M d, Y').'. Open the Career Hub for the approved care summary.',
            'url' => route('alumni.dashboard'),
            'icon' => 'briefcase',
            'opportunity_id' => $this->opportunity->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New MCARE career opportunity')
            ->line('A privacy-reviewed career is available in the MCARE Career Hub.')
            ->action('Open Career Hub', route('alumni.dashboard'));
    }
}
