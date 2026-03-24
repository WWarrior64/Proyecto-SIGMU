<?php

declare(strict_types=1);

session_start();

$router = require __DIR__ . '/../bootstrap/app.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
