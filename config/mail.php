<?php

return [
    'transport' => $_ENV['MAIL_TRANSPORT'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? '127.0.0.1',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 1025),
    'username' => $_ENV['MAIL_USERNAME'] ?? null,
    'password' => $_ENV['MAIL_PASSWORD'] ?? null,
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? null,
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@sigmu.local',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'SIGMU',
];
