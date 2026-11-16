<?php
// Explicitly apply local .env credentials to the existing admin, preserving its ID.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! $app->environment('local')) {
    throw new RuntimeException('This helper is only for the local development environment.');
}
$settings = Dotenv\Dotenv::createArrayBacked(__DIR__.'/../backend')->load();
$email = strtolower(trim($settings['ADMIN_EMAIL'] ?? ''));
$password = $settings['ADMIN_PASSWORD'] ?? '';
if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    throw new RuntimeException('Set a valid ADMIN_EMAIL and ADMIN_PASSWORD of at least 8 characters in backend/.env.');
}
$apply = ($argv[1] ?? '') === '--apply';
Illuminate\Support\Facades\DB::transaction(function () use ($email, $password, $apply) {
    $admin = App\Models\User::where('email', $email)->lockForUpdate()->first();
    if ($admin && $admin->role !== 'admin') {
        throw new RuntimeException('The requested email belongs to a non-admin account. No changes made.');
    }
    if (! $admin) {
        $admins = App\Models\User::where('role', 'admin')->lockForUpdate()->get();
        if ($admins->count() !== 1) {
            throw new RuntimeException('Expected exactly one existing administrator for an email change. No changes made.');
        }
        $admin = $admins->first();
    }
    echo 'Database: '.Illuminate\Support\Facades\DB::connection()->getDatabaseName().PHP_EOL;
    echo 'Admin #'.$admin->id.': '.$admin->email.' -> '.$email.PHP_EOL;
    if (! $apply) {
        echo 'Preview only. Use --apply to sync this existing account.'.PHP_EOL;
        return;
    }
    $admin->update(['email' => $email, 'password' => $password, 'is_active' => true]);
    if (! Illuminate\Support\Facades\Hash::check($password, $admin->fresh()->password)) {
        throw new RuntimeException('Password verification failed; transaction rolled back.');
    }
    echo 'Existing admin updated; password hash verified. No extra account created.'.PHP_EOL;
});
