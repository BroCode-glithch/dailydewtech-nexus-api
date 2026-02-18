<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Message</title>
</head>

<body style="font-family:Arial, Helvetica, sans-serif; background:#f7f7fb; padding:20px; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="600"
                    style="max-width:600px;background:#ffffff;border-radius:8px;padding:18px;">
                    <tr>
                        <td style="padding:12px 18px;">
                            <h2 style="margin:0 0 8px 0;font-size:18px;color:#0f172a;">New Contact Message</h2>
                            <p style="margin:0 0 12px 0;color:#6b7280;">A new message was submitted from the contact
                                form.</p>

                            <table style="width:100%;margin-top:8px;border-collapse:collapse;">
                                <tr>
                                    <td style="font-weight:600;padding:6px 0;width:120px;color:#374151;">Name</td>
                                    <td style="padding:6px 0;color:#374151;">{{ $contact->name }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;padding:6px 0;color:#374151;">Email</td>
                                    <td style="padding:6px 0;color:#374151;">{{ $contact->email }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;padding:6px 0;color:#374151;">Subject</td>
                                    <td style="padding:6px 0;color:#374151;">{{ $contact->subject }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;padding:6px 0;color:#374151;vertical-align:top;">Message
                                    </td>
                                    <td style="padding:6px 0;color:#374151;">{!! nl2br(e($contact->message)) !!}</td>
                                </tr>
                            </table>

                            <p style="margin-top:18px;color:#9ca3af;font-size:13px;">Received:
                                {{ $contact->created_at }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
