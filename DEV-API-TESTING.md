API Runtime Test Instructions (Windows PowerShell)

1. Prepare environment

- Ensure PHP, Composer and the database are configured and the app key is set:

    composer install
    cp .env.example .env

    # edit .env for DB credentials and APP_URL

    php artisan key:generate
    php artisan migrate --seed

2. Run the test suite (PHPUnit)

In PowerShell:

    ./vendor/bin/phpunit --filter ApiAuthTest

This will run the feature tests added in `tests/Feature/ApiAuthTest.php`.

3. Quick curl/PowerShell examples (manual smoke tests)

- Register (creates user and returns token):

    $reg = Invoke-RestMethod -Method Post -Uri http://localhost/api/register -Body (@{ name='Test'; email='test@example.com'; password='password'; password_confirmation='password' } | ConvertTo-Json) -ContentType 'application/json'
    $token = $reg.token

- Login (returns token):

    $login = Invoke-RestMethod -Method Post -Uri http://localhost/api/login -Body (@{ email='test@example.com'; password='password' } | ConvertTo-Json) -ContentType 'application/json'
    $token = $login.token

- Access protected endpoint (example):

    Invoke-RestMethod -Method Get -Uri http://localhost/api/admin/quotes -Headers @{ Authorization = "Bearer $token" }

Notes

- The repository's test runner cannot be executed from this environment (no PHP in PATH). Run the commands above locally on your machine where WAMP provides PHP.
- If your app uses a different base URL or port, update the URLs accordingly.
