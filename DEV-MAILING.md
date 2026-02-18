Local SMTP (MailHog) setup and quick tests

1. Install MailHog (Windows)

- Download MailHog for Windows or use the binary from https://github.com/mailhog/MailHog/releases
- Run MailHog (it listens on port 1025 for SMTP and port 8025 for the web UI):

    MailHog.exe

2. Configure .env

Set these values (example already set in the repo .env):

    MAIL_MAILER=smtp
    MAIL_HOST=127.0.0.1
    MAIL_PORT=1025
    MAIL_USERNAME=
    MAIL_PASSWORD=
    MAIL_ENCRYPTION=null

3. Test sending an email from artisan tinker

    & 'C:\\wamp64\\bin\\php\\php8.3.14\\php.exe' artisan tinker

    > > > Mail::to('you@example.com')->send(new App\\Mail\\LoginVerificationCode(\App\\Models\\User::first(), '123456'))

Open http://127.0.0.1:8025 to see the delivered message in MailHog's web UI.

4. Alternative: create a debug route to send a test mail

Add a temporary route and call it from your browser to verify mails are delivered.
