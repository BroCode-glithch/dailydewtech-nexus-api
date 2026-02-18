<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the console kernel so facades and service providers are available
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Mail\LoginVerificationCode;
use Illuminate\Support\Facades\Mail;

$u = User::first();
if (! $u) {
    echo "no-user\n";
    exit(1);
}

Mail::to($u->email)->send(new LoginVerificationCode($u, '123456'));

echo "sent-to:" . $u->email . "\n";
