<?php

return [
    'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'path' => __DIR__ . '/../storage/cache',
];
