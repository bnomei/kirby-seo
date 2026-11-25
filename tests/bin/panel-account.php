<?php

declare(strict_types=1);

use Kirby\Cms\User;

$kirby = require dirname(__DIR__) . '/app.php';
$email = 'editor@example.test';
if ($kirby->users()->findBy('email', $email) === null) {
    $password = bin2hex(random_bytes(24));
    $kirby->impersonate('kirby', fn () => User::create([
        'email' => $email,
        'name' => 'Test Editor',
        'role' => 'admin',
        'password' => $password,
    ]));
    umask(0077);
    file_put_contents(dirname(__DIR__) . '/runtime/panel-password', $password);
}
