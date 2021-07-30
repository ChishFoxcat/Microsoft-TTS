<?php

// Token 获取地址
$config['token_url'] = '';

$config['api'] = '';
$config['api_version'] = 'v1';

$config['api_list'] = [
    'voices_list'  => [
        'path'     => '/voices/list',
        'hasVer'   => false
    ],
    'text_to_voice' => [
        'path'   => '',
        'hasVer' => true
    ]
    
];

// 密钥
$config['token_key'] = [
    'N/A',
    'N/A'
];

// HTTP 访问令牌
$config['access_token'] = 'N/A';

return $config;