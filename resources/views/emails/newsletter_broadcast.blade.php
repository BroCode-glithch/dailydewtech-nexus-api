<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111;">
    <p>Hello{{ !empty($subscriber->name) ? ' ' . $subscriber->name : '' }},</p>

    <div>
        {!! nl2br(e($contentBody)) !!}
    </div>

    <hr style="margin: 24px 0;">

    <p style="font-size: 12px; color: #666;">
        You are receiving this email because you subscribed to our newsletter.
    </p>

    <p style="font-size: 12px; color: #666;">
        To unsubscribe, call the API endpoint with your email and token:
        <br>
        <strong>Token:</strong> {{ $subscriber->unsubscribe_token }}
    </p>
</body>

</html>
