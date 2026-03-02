<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $subjectLine }}</title>
</head>

<body style="margin:0;padding:0;background:#0b1220;">
    <!-- Preheader (hidden) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $subjectLine }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#0b1220;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <!-- Outer container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;border-collapse:collapse;">
                    <!-- Color header -->
                    <tr>
                        <td style="padding:0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="
                                        background: linear-gradient(90deg, #ff3d81 0%, #7c3aed 45%, #22d3ee 100%);
                                        border-radius:18px 18px 0 0;
                                        padding:22px 22px;
                                    ">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                            <tr>
                                                <td valign="middle">
                                                    <div style="font-family:Arial, Helvetica, sans-serif;color:#ffffff;">
                                                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.9;">
                                                            Newsletter
                                                        </div>
                                                        <div style="font-size:20px;line-height:1.25;font-weight:800;margin-top:6px;">
                                                            {{ $subjectLine }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <div style="
                                                        font-family:Arial, Helvetica, sans-serif;
                                                        font-size:12px;
                                                        color:#ffffff;
                                                        background:rgba(255,255,255,0.18);
                                                        border:1px solid rgba(255,255,255,0.25);
                                                        padding:8px 12px;
                                                        border-radius:999px;
                                                        white-space:nowrap;
                                                    ">
                                                        ✦ Fresh update
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main card -->
                    <tr>
                        <td style="
                            background:#ffffff;
                            border-radius:0 0 18px 18px;
                            padding:24px 22px 18px 22px;
                            box-shadow:0 18px 40px rgba(0,0,0,0.35);
                        ">
                            <!-- Greeting -->
                            <p style="margin:0 0 14px 0;font-family:Arial, Helvetica, sans-serif;font-size:15px;line-height:1.6;color:#0f172a;">
                                <span style="font-weight:700;">Hello{{ !empty($subscriber->name) ? ' ' . $subscriber->name : '' }},</span>
                            </p>

                            <!-- Content block -->
                            <div style="
                                font-family:Arial, Helvetica, sans-serif;
                                font-size:15px;
                                line-height:1.75;
                                color:#0f172a;
                            ">
                                @if (($contentFormat ?? 'plain_text') === 'html')
                                    {!! $contentBody !!}
                                @else
                                    {!! nl2br(e($contentBody)) !!}
                                @endif
                            </div>

                            <!-- Divider -->
                            <div style="height:1px;background:#e5e7eb;margin:22px 0;"></div>

                            <!-- Footer note -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;line-height:1.6;color:#64748b;">
                                        You are receiving this email because you subscribed to our newsletter.
                                    </td>
                                </tr>
                            </table>

                            <!-- Unsubscribe card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:14px;">
                                <tr>
                                    <td style="
                                        background:#0b1220;
                                        border-radius:14px;
                                        padding:14px 14px;
                                        border:1px solid rgba(148,163,184,0.22);
                                    ">
                                        <div style="font-family:Arial, Helvetica, sans-serif;color:#e2e8f0;">
                                            <div style="font-size:12px;opacity:0.9;">
                                                To unsubscribe, call the API endpoint with your email and token:
                                            </div>

                                            <div style="
                                                margin-top:10px;
                                                padding:10px 12px;
                                                border-radius:12px;
                                                background:linear-gradient(90deg, rgba(255,61,129,0.18), rgba(124,58,237,0.18), rgba(34,211,238,0.18));
                                                border:1px solid rgba(255,255,255,0.14);
                                            ">
                                                <div style="font-size:12px;color:#cbd5e1;margin:0 0 6px 0;">
                                                    <strong style="color:#ffffff;">Token:</strong>
                                                </div>
                                                <div style="
                                                    font-size:13px;
                                                    color:#ffffff;
                                                    font-weight:700;
                                                    word-break:break-all;
                                                    letter-spacing:0.02em;
                                                ">
                                                    {{ $subscriber->unsubscribe_token }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tiny bottom spacing -->
                            <div style="height:10px;"></div>
                        </td>
                    </tr>

                    <!-- Bottom spacing -->
                    <tr>
                        <td style="padding:16px 8px 0 8px;">
                            <div style="font-family:Arial, Helvetica, sans-serif;font-size:11px;line-height:1.6;color:#94a3b8;text-align:center;">
                                © {{ config('app.name') ?? 'Our Company' }} — crafted with color.
                            </div>
                        </td>
                    </tr>
                </table>
                <!-- /Outer container -->
            </td>
        </tr>
    </table>
</body>
</html>