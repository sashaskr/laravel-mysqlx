<?php

return [
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('MYSQL_HOST', 'mysql'),
            'port' => env('MYSQL_PORT') ? (int) env('MYSQL_PORT') : 3306,
            'xport' => env('MYSQL_XPORT') ? (int) env('MYSQL_XPORT') : 33060,
            'database' => env('MYSQL_DATABASE', 'unittest'),
            'username' => env('MYSQL_USERNAME', 'root'),
            'password' => env('MYSQL_PASSWORD', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ],
    ],
];