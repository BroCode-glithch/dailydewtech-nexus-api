<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Comment $comment,
        protected ?Story $story = null,
        protected ?Chapter $chapter = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->story?->title ?? $this->chapter?->title ?? 'your comment';

        return (new MailMessage)
            ->subject('New reply to your comment')
            ->line("Someone replied to your comment on {$title}.")
            ->line('Open the app to view the reply.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'comment_id' => $this->comment->id,
            'story_id' => $this->story?->id,
            'chapter_id' => $this->chapter?->id,
            'message' => 'Someone replied to your comment.',
        ];
    }
}
