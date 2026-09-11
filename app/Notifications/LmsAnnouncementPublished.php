<?php

namespace App\Notifications;

use App\Models\TrainerAnnouncement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LmsAnnouncementPublished extends Notification
{
    public function __construct(
        public TrainerAnnouncement $announcement,
    ) {}

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
            'title' => $this->announcement->title,
            'message' => str($this->announcement->message)->limit(140)->toString(),
            'url' => route('trainee.stream'),
            'icon' => 'bell',
            'announcement_id' => $this->announcement->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trainerName = $this->announcement->trainer?->name ?? 'Your Trainer';

        return (new MailMessage)
            ->subject('MCARE Class Announcement: '.$this->announcement->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($trainerName.' posted a new classroom update:')
            ->line('**'.$this->announcement->title.'**')
            ->line($this->announcement->message)
            ->action('Open Classroom Stream', route('trainee.stream'))
            ->line('Stay up to date with your class schedule and learning announcements.')
            ->salutation('MCARE Training Center');
    }
}
