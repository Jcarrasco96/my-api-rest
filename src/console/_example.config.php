<?php

return [
    'jwtSecretKey' => '{{jwtSecretKey}}',
    'origins' => ['*'],
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
    'blockedIPsFile' => 'blocked_ips.txt',
    'mail' => [
        'host' => 'smtp.gmail.com',
        'username' => 'usuario@gmail.com',
        'password' => '{{mail_password}}',
        'encryption' => 'tls',
        'port' => 587,
        'options' => [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]
    ],
    'params' => [
        'supportEmail' => 'support@shortenit.com'
    ]
];