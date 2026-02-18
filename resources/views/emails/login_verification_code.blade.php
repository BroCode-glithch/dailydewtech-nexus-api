<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Verification | {{ config('app.name') }}</title>
  </head>
  <body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f3f4f6;padding:24px 0;">
      <tr>
        <td align="center">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(16,24,40,0.08);">

            <!-- Header With Online Upsplash Slider Images -->
            <tr>
              <td style="padding:20px 28px;">
                <img src="https://source.unsplash.com/600x200/?nature,water" alt="Nature Image" style="width:100%;height:auto;display:block;">
                <h1 style="margin:0;font-size:18px;font-weight:700;letter-spacing:0.2px;">{{ config('app.name') }}</h1>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:28px;color:#0f172a;">
                <p style="margin:0 0 16px 0;font-size:15px;line-height:1.5;color:#374151;">Hi {{ $user->name }},</p>

                <p style="margin:0 0 22px 0;font-size:15px;line-height:1.5;color:#374151;">You requested a login verification code for your {{ config('app.name') }} account. Use the code below to complete your sign-in. This code is valid for 10 minutes.</p>

                <!-- Code box -->
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:18px 0 24px 0;">
                  <tr>
                    <td align="center">
                      <div style="display:inline-block;padding:18px 28px;background:#0f172a;color:#ffffff;border-radius:6px;font-size:28px;letter-spacing:4px;font-weight:700;">{{ $code }}</div>
                    </td>
                  </tr>
                </table>

                <p style="margin:0 0 18px 0;font-size:14px;color:#6b7280;">If the button below does not work, copy and paste the code into the app.</p>

                <!-- CTA button (optional link back to site) -->
                <p style="margin:0 0 26px 0;">
                  <a href="{{ config('app.frontend_url') }}" style="display:inline-block;padding:12px 20px;background:#06b6d4;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;">Return to {{ config('app.name') }}</a>
                </p>

                <p style="margin:0 0 4px 0;font-size:13px;color:#9ca3af;">This code will expire in 10 minutes. If you did not request this code, you can safely ignore this email.</p>

                <hr style="border:none;border-top:1px solid #eef2f7;margin:22px 0;">

                <p style="margin:0;font-size:13px;color:#9ca3af;">If you need help, contact us at <a href="mailto:{{ config('mail.info.address') }}" style="color:#6b7280;text-decoration:underline;">{{ config('mail.info.address') }}</a></p>
              </td>
            </tr>

            <!-- Footer with social media links -->
            <tr>
              <td style="background:#f8fafc;padding:14px 20px;text-align:center;color:#9ca3af;font-size:12px;">
                <div>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
              </td>
            </tr>

            <tr>
              <td style="background:#f8fafc;padding:14px 20px;text-align:center;color:#9ca3af;font-size:12px;">
                <div>Follow us on:</div>
                <div>
                  <a href="#" style="color:#6b7280;text-decoration:underline;">Facebook</a> |
                  <a href="#" style="color:#6b7280;text-decoration:underline;">Twitter</a> |
                  <a href="#" style="color:#6b7280;text-decoration:underline;">Instagram</a>
                </div>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
