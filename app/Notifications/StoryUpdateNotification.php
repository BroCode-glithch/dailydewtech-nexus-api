<?php

namespace App\Notifications;

use App\Models\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StoryUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(protected Story $story, protected string $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Story update')
            ->line($this->message)
            ->line($this->story->title);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'story_update',
            'story_id' => $this->story->id,
            'message' => $this->message,
        ];
    }
}
