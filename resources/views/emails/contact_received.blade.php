<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Message</title>
</head>

<body style="margin:0;padding:0;background:#0b1220;font-family:Arial, Helvetica, sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#0b1220;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:640px;border-collapse:collapse;">
                    <!-- Header / Gradient -->
                    <tr>
                        <td style="
                            background: linear-gradient(90deg, #ff3d81 0%, #7c3aed 45%, #22d3ee 100%);
                            border-radius:20px 20px 0 0;
                            padding:22px 22px;
                        ">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td valign="middle">
                                        <div style="color:#ffffff;">
                                            <div style="font-size:12px;letter-spacing:0.14em;text-transform:uppercase;opacity:0.9;">
                                                Contact Form
                                            </div>
                                            <div style="font-size:20px;line-height:1.25;font-weight:800;margin-top:6px;">
                                                New Contact Message
                                            </div>
                                        </div>
                                    </td>
                                    <td align="right" valign="middle">
                                        <div style="
                                            font-size:12px;
                                            color:#ffffff;
                                            background:rgba(255,255,255,0.18);
                                            border:1px solid rgba(255,255,255,0.25);
                                            padding:8px 12px;
                                            border-radius:999px;
                                            white-space:nowrap;
                                        ">
                                            ✉ Incoming
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td style="
                            background:#ffffff;
                            border-radius:0 0 20px 20px;
                            padding:22px 22px 18px 22px;
                            box-shadow:0 18px 42px rgba(0,0,0,0.38);
                        ">
                            <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#475569;">
                                A new message was submitted from the contact form. Details below:
                            </p>

                            <!-- Details card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="
                                border-collapse:separate;
                                border-spacing:0;
                                background:#f8fafc;
                                border:1px solid #e5e7eb;
                                border-radius:16px;
                                overflow:hidden;
                            ">
                                <!-- Row: Name -->
                                <tr>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;width:170px;">
                                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
                                            Name
                                        </div>
                                    </td>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;">
                                        <div style="font-size:14px;color:#0f172a;font-weight:700;">
                                            {{ $contact->name }}
                                        </div>
                                    </td>
                                </tr>

                                <!-- Row: Email -->
                                <tr>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;width:170px;">
                                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
                                            Email
                                        </div>
                                    </td>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;">
                                        <div style="font-size:14px;color:#0f172a;">
                                            {{ $contact->email }}
                                        </div>
                                    </td>
                                </tr>

                                <!-- Row: Subject -->
                                <tr>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;width:170px;">
                                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
                                            Subject
                                        </div>
                                    </td>
                                    <td style="padding:14px 14px;border-bottom:1px solid #e5e7eb;">
                                        <div style="
                                            display:inline-block;
                                            font-size:13px;
                                            color:#0f172a;
                                            font-weight:700;
                                            padding:8px 10px;
                                            border-radius:999px;
                                            border:1px solid rgba(124,58,237,0.25);
                                            background:linear-gradient(90deg, rgba(255,61,129,0.10), rgba(124,58,237,0.10), rgba(34,211,238,0.10));
                                        ">
                                            {{ $contact->subject }}
                                        </div>
                                    </td>
                                </tr>

                                <!-- Row: Message -->
                                <tr>
                                    <td style="padding:14px 14px;vertical-align:top;width:170px;">
                                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
                                            Message
                                        </div>
                                    </td>
                                    <td style="padding:14px 14px;">
                                        <div style="
                                            font-size:14px;
                                            line-height:1.75;
                                            color:#0f172a;
                                            background:#ffffff;
                                            border:1px solid #e5e7eb;
                                            border-radius:14px;
                                            padding:12px 12px;
                                        ">
                                            {!! nl2br(e($contact->message)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Meta -->
                            <div style="margin-top:16px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td>
                                            <div style="font-size:12px;color:#64748b;">
                                                Received:
                                                <span style="font-weight:700;color:#0f172a;">{{ $contact->created_at }}</span>
                                            </div>
                                        </td>
                                        <td align="right">
                                            <div style="
                                                font-size:12px;
                                                color:#e2e8f0;
                                                background:#0b1220;
                                                border:1px solid rgba(148,163,184,0.24);
                                                padding:8px 10px;
                                                border-radius:12px;
                                            ">
                                                Priority: <span style="color:#ffffff;font-weight:700;">Normal</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Footer -->
                            <div style="height:1px;background:#e5e7eb;margin:18px 0 14px 0;"></div>
                            <div style="font-size:11px;line-height:1.6;color:#94a3b8;">
                                This is an automated notification from your website contact form.
                            </div>
                        </td>
                    </tr>

                    <!-- Bottom note -->
                    <tr>
                        <td style="padding:16px 8px 0 8px;">
                            <div style="font-size:11px;line-height:1.6;color:#94a3b8;text-align:center;">
                                © {{ config('app.name') ?? 'Your App' }} — colorful notifications, clean details.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>