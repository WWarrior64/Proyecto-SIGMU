<?php

return [
    'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
    'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 900),
    'path' => __DIR__ . '/../storage/sessions',
];
