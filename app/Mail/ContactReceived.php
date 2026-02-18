<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Messages;

class ContactReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $messageData;

    /**
     * Create a new message instance.
     */
    public function __construct(Messages $message)
    {
        $this->messageData = $message;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // pass as 'contact' to avoid collision with mailer's internal $message variable in views
        return $this->subject('New contact message')->view('emails.contact_received')->with(['contact' => $this->messageData]);
    }
}
