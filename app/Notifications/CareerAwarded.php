<?php

namespace App\Notifications;

use App\Models\CareerInquiry;
use Illuminate\Notifications\Notification;

class CareerAwarded extends Notification
{
    public function __construct(
        public CareerInquiry $inquiry,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $career = $this->inquiry->opportunity?->listingTitle() ?? 'your career';

        return [
            'title' => 'Career awarded',
            'message' => 'MCARE approved your placement certificate. '.$career.' is now awarded in your career history.',
            'url' => route('trainee.achievements'),
            'icon' => 'award',
            'inquiry_id' => $this->inquiry->id,
            'opportunity_id' => $this->inquiry->career_opportunity_id,
        ];
    }
}
