<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterBroadcast extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $contentBody;
    public NewsletterSubscriber $subscriber;
    public string $contentFormat;

    public function __construct(string $subjectLine, string $contentBody, NewsletterSubscriber $subscriber)
    {
        $this->subjectLine = $subjectLine;
        $this->contentBody = $contentBody;
        $this->subscriber = $subscriber;
        $this->contentFormat = 'plain_text';
    }

    public function setContentFormat(string $contentFormat): self
    {
        $this->contentFormat = $contentFormat;

        return $this;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.newsletter_broadcast')
            ->with([
                'subjectLine' => $this->subjectLine,
                'contentBody' => $this->contentBody,
                'subscriber' => $this->subscriber,
                'contentFormat' => $this->contentFormat,
            ]);
    }
}
