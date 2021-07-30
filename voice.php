<?php

define('__TTS_ROOT_DIR__', __DIR__);

foreach ([
    __TTS_ROOT_DIR__ . "/vendor/autoload.php",
    __TTS_ROOT_DIR__ . "/autoload.php"
] as $file) {
    if (file_exists($file)) {
        define('__TTS_COMPOSER_INSTALL__', $file);
        break;
    }
}

if (!defined('__TTS_COMPOSER_INSTALL__')) {
    echo 'You need to set up the project dependencies using Composer: <code>composer install</code>' .
        '<br />You can learn all about Composer on https://getcomposer.org/.';

    exit;
} else {
    // 引入自动加载
    require_once __TTS_COMPOSER_INSTALL__;
}
(new \tts\tts())->init();
