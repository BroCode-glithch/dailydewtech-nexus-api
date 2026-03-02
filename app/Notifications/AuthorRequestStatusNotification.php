<?php

namespace App\Notifications;

use App\Models\AuthorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AuthorRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected AuthorRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->request->status;
        $subject = $status === 'approved' ? 'Author request approved' : 'Author request update';

        $message = $status === 'approved'
            ? 'Your author request has been approved. You can now access the author dashboard.'
            : 'Your author request has been reviewed.';

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Open the app for details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'author_request_status',
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'message' => $this->request->status === 'approved'
                ? 'Your author request was approved.'
                : 'Your author request was reviewed.',
        ];
    }
}
