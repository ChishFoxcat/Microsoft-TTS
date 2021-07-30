<?php

namespace tts\Config;

class Config {
    
    private static $_config;

    private static $_path = '.';

    public static function setDir($path) {
        return self::$_path = $path;
    }

    public static function get() {
        $file = self::$_path . '/config.php';

        if (!file_exists($file)) {
            return false;
        }

        return self::$_config = include_once $file;
    }

    public static function tmpGet() {
        return self::$_config;
    }
}