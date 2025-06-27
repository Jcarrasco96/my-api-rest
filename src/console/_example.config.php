<?php

return [
    'jwtSecretKey' => '{{jwtSecretKey}}',
    'origins' => [
        '*'
    ],
    'db' => [
        'driver' => '{{driver}}',
        '{{driver}}' => [
            'host' => '{{host}}',
            'port' => '{{port}}',
            'username' => '{{username}}',
            'password' => '{{password}}',
            'database' => '{{database}}',
            'charset' => 'utf-8',
        ]
    ],
    'controllerNamespace' => '{{controllerNamespace}}',
    'modelNamespace' => '{{modelNamespace}}',
    'repositoryNamespace' => '{{repositoryNamespace}}',
    'userModel' => '{{userModel}}',

    'mail' => [
        'host' => 'smtp.gmail.com',
        'username' => 'usuario@gmail.com',
        'password' => '{{mail_password}}',
        'port' => 587,
        'encryption' => 'tls',
    ],
];