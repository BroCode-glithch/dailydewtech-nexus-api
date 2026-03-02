<?php

namespace App\Notifications;

use App\Models\AuthorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewAuthorRequestNotification extends Notification
{
    use Queueable;

    public function __construct(protected AuthorRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New author request submitted')
            ->line('A new author request is awaiting review.')
            ->line("Applicant: {$this->request->user->email}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'author_request_new',
            'request_id' => $this->request->id,
            'user_id' => $this->request->user_id,
            'message' => 'A new author request is awaiting review.',
        ];
    }
}
