<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// FrankenPHP runtime handler
if (function_exists('frankenphp_handle_request')) {
    frankenphp_handle_request(function () {
        require __DIR__.'/../vendor/autoload.php';
        $app = require_once __DIR__.'/../bootstrap/app.php';

        $kernel = $app->make(Kernel::class);

        $response = $kernel->handle(
            $request = Request::capture()
        )->send();

        $kernel->terminate($request, $response);
    });

    return;
}

// Fallback biasa
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
