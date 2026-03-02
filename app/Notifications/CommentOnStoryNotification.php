<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommentOnStoryNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Comment $comment,
        protected Story $story,
        protected ?Chapter $chapter = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New comment on your story')
            ->line("You have a new comment on {$this->story->title}.")
            ->line('Open the app to review and reply.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'story_comment',
            'comment_id' => $this->comment->id,
            'story_id' => $this->story->id,
            'chapter_id' => $this->chapter?->id,
            'message' => 'A reader commented on your story.',
        ];
    }
}
