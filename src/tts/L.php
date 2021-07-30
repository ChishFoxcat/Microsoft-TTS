<?php

namespace tts;

use tts\Http\Http;
use tts\Libs\Curl;
use tts\Libs\Libs;

class L
{

    private static $_config;

    private static $_api = [];

    private static $_cache_dir;

    private static $_data_dir;

    public static function __init($config, $api, $cache, $data)
    {
        self::$_config = $config;
        self::$_api = $api;
        self::$_cache_dir = $cache;
        self::$_data_dir = $data;
    }

    /**
     * 首页
     * 
     * @path /
     */
    public static function Home_Action($response)
    {
        Http::setStatus(403);
        Http::setHeader('Content-type', 'application/json; charset=utf-8');

        echo json_encode([
            "name"      =>  "Chish's Voice API Site",
            "version"   =>  self::$_config["api_version"],
            "links"     =>  [
                "kms"     => "https://kms.fchish.cn",
                "github"  => "https://github.com/ChishFoxcat"
            ],
            "message"   =>  "Meow~ ?"
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取语音列表
     * 
     * @path /voices/list
     */
    public static function Voices_List_Action($response)
    {
        Http::lockMethod('GET');
        Http::auth();

        $list = json_decode(self::send('voices_list', 'GET', [
            'Ocp-Apim-Subscription-Key: ' . self::$_config['token_key'][0]
        ], [], false), true);

        if (empty($list)) {
            Http::jsonData('No Data', [], false, 404);
        }

        $result = [];

        foreach ($list as $item) {
            if ($item['Locale'] == 'zh-CN') {
                $result[] = $item;
            }
        }

        Http::jsonData('Success', $result, true, 200);
    }

    /**
     * 语音转文字
     * 
     * @path /text-to-voice/[:speaker]
     */
    public static function TextToVoice_Action($response, $match)
    {
        Http::lockMethod(['POST', 'GET']);
        Http::auth();

        $speaker = isset($match[1]) ? $match[1] : "zh-CN-XiaoxiaoNeural";

        // 讲话风格
        // 注意：仅部分语音支持
        $style = Http::getParams('style', '');

        // 情感风格强度
        $styledegree = Http::getParams('styledegree', 2);

        // 角色扮演
        $role = Http::getParams('role', '');

        // 文字内容
        $text = Http::getParams('text', null);

        if (!isset($text) || empty($text) || $text == ' ') {
            Http::jsonData('待转文本不能为空', [], false, 400);
        }

        if ((int)$styledegree > 2 || (int)$styledegree < 0) {
            Http::jsonData('情感风格强度数值错误，范围: 0-2', [], false, 400);
        }

        $cache_dir = self::$_cache_dir . "/voices/" . $speaker . "/";

        $cache_file = $cache_dir . Libs::uuid($text) . ".audio";

        if (file_exists($cache_file)) {
            Http::setHeader('Content-Type', 'audio/mpeg');
            echo file_get_contents($cache_file);

            exit;
        }


        $roles = $speaker == 'zh-CN-XiaomoNeural' || $speaker == 'zh-CN-XiaoxuanNeural' ? ' role="' . $role . '" ' : ' ';

        $SSML = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xmlns:mstts="https://www.w3.org/2001/mstts" xml:lang="zh-CN">';
        $SSML .= '<voice name="' . $speaker . '">';
        $SSML .= '<mstts:express-as' . $roles . 'style="' . $style . '" styledegree="' . $styledegree . '">';
        $SSML .= $text;
        $SSML .= '</mstts:express-as>';
        $SSML .= '</voice>';
        $SSML .= '</speak>';

        $result = self::send('text_to_voice', 'POST', [
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat: audio-48khz-192kbitrate-mono-mp3',
            'User-Agent: Chish/VoiceServer',
        ], $SSML, true);

        if (empty($result) || @$result['error'] === true) {
            Http::jsonData('内部错误', [], false, 500);
        }

        Http::setHeader('Content-Type', 'audio/mpeg');
        echo $result;

        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0777, true);
            chmod($cache_dir, 0777);
        }

        file_put_contents($cache_file, $result);
        file_put_contents($cache_dir . Libs::uuid($text) . ".info.json", json_encode([
            'uuid'         => Libs::uuid($text),
            'file'         => $cache_file,
            'time'         => time(),
            'speaker'      => $speaker,
            'style'        => $style,
            'styledegree'  => $styledegree,
            'role'         => $role,
            'text'         => $text
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取令牌
     * 
     * @return array
     * @access public
     */
    private static function token(): array
    {
        $file = self::$_data_dir . "/token.json";
        $_cache = false;

        // 没有的话刷新即获取令牌
        if (!file_exists($file)) {
            $token = Libs::__getToken($file, self::$_config['token_url'], self::$_config['token_key'][rand(0, count(self::$_config['token_key']) - 1)]);
            $_cache = false;
        } else {
            $tkArr = json_decode(file_get_contents($file), true);

            // 判断是否失效
            if (time() - $tkArr['last_update']  > 540) {
                $token = Libs::__getToken($file, self::$_config['token_url'], self::$_config['token_key'][rand(0, count(self::$_config['token_key']) - 1)]);
                $_cache = false;
            } else {
                $token = $tkArr;
                $_cache = true;
            }
        }

        return [
            'cache'  =>  $_cache,
            'token'  =>  $token['token']
        ];
    }

    /**
     * 发送请求
     * 
     * @access public
     */
    private static function send($api_name, $method = 'GET', $header = [], $data = [], $needToken = true)
    {
        if (empty(self::$_api[$api_name])) {
            return false;
        }

        $method = (strtolower($method) == 'get') ? 'get' : 'post';

        $head = $needToken === true ? [
            'Authorization: Bearer ' . self::token()['token']
        ] : [];

        $curl = Curl::$method(self::$_api[$api_name], $data, array_merge($head, $header));

        return empty($curl) ? false : $curl;
    }
}
