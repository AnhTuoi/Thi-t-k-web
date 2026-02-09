<?php
// config/email_config.php
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'email2@gmail.com',
        'password' => 'password',
        'encryption' => 'tls',
        'from_email' => 'no-reply@foodgo.vn',
        'from_name' => 'FoodGo',
    ],
    'debug' => true,
];

