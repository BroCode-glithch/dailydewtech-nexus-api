<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::first();
$post = App\Models\Posts::create([
    'title' => 'CLI Test Post',
    'content' => '<p>This is test content created from CLI. Excerpt should be generated automatically.</p>',
    'user_id' => $u ? $u->id : null,
]);

echo json_encode(App\Models\Posts::find($post->id), JSON_PRETTY_PRINT);
