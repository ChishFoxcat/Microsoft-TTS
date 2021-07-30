<?php

namespace tts;

use tts\Config\Config;
use tts\Http\Http;
use tts\Libs\Libs;


class tts {

    private $_config;

    private $_api;

    private $_cache_dir = __TTS_ROOT_DIR__ . '/cache';
    
    private $_data_dir = __TTS_ROOT_DIR__ . '/data';

    public function __construct() {
        Config::setDir($this->_data_dir);

        $this->_config = Config::get();

        foreach ($this->_config['api_list'] as $key => $item) {
            $_root_api = $item['hasVer'] === true ? $this->_config['api'] . '/' . $this->_config['api_version'] : $this->_config['api'];
            $this->_api[$key] = $_root_api . $item['path'];
        }

        \tts\L::__init($this->_config, $this->_api, $this->_cache_dir, $this->_data_dir);

        Http::__init();
    }

    public function init() {
        Http::dispatch(Libs::getRoutes('\tts\L'), '\tts\L');
    }
}