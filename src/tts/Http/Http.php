<?php

namespace tts\Http;

class Http
{
    private static $_http_code;

    private static $_server = [];

    public static function __init()
    {
        self::$_http_code = json_decode(file_get_contents(__TTS_ROOT_DIR__ . "/data/http_code.json"), true);
    }

    /**
     * 路由分发
     */
    public static function dispatch($list, $class)
    {
        foreach ($list as $route) {
            Router::add($route['name'], $route['uri'], $class, $route['action']);
        }

        $status = false;
        $_pathInfo = preg_replace('/\?(.*)/i', '', $_SERVER['REQUEST_URI']);

        foreach (Router::getRoutePaserTable() as $key => $route) {
            if (preg_match($route['regx'], $_pathInfo, $matches)) {
                $status = true;

                call_user_func_array([$route['widget'], $route['action']], [self::getResponse(), $matches]);
            }
        }

        if ($status === false) {
            self::jsonData('Not Found', [], false, 404);
        }
    }

    /**
     * 以 JSON 发送请求
     * 
     */
    public static function jsonData($message = '', $data = [], $status = true, $code = 200)
    {
        $Status = ($code == '0') ? 200 : $code;
        self::setStatus($Status);

        self::setHeader('Content-type', 'application/json; charset=utf-8');

        $arr = [
            'code'     =>  $code,
            'status'   => ($status === true) ? true : false,
            'message'  =>  $message,
            'data'     =>  $data
        ];

        $json = json_encode($arr, JSON_UNESCAPED_UNICODE);

        echo $json;

        // 终止后续内容输出
        exit;
    }

    /**
     * 锁定请求方式
     * 
     */
    public static function lockMethod($method)
    {
        if (is_array($method)) {
            $_methods = [];
            foreach ($method as $val) {
                $_methods[] = strtolower($val);
            }
            if (!in_array(strtolower(self::getServer('REQUEST_METHOD')), $_methods)) self::_LockMethodMsg();
        } else {
            if (strtolower(self::getServer('REQUEST_METHOD')) != strtolower($method)) self::_LockMethodMsg();
        }
    }

    /**
     * 锁定 API 请求方式输出函数
     * 
     * @access private 
     */
    private static function _LockMethodMsg()
    {
        self::jsonData(
            'Method not allowed',
            [],
            false,
            405
        );
    }

    /**
     * 设置 HTTP 状态
     * 
     * @param integer $code   http代码
     */
    public static function setStatus($code)
    {
        if (isset(self::$_http_code[$code])) {
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' ' . $code . ' ' . self::$_http_code[$code], true, $code);
        }
    }

    /**
     * 设置 HTTP 头
     * 
     * @param string $name   名称
     * @param string $value  对应值
     */
    public static function setHeader($name, $value)
    {
        header($name . ': ' . $value, true);
    }

    /**
     * 获取环境变量
     *
     * @access public
     * @param string $name 获取环境变量名
     * @return string
     */
    public static function getServer($name)
    {
        if (!isset(self::$_server[$name])) {
            self::setServer($name);
        }

        return self::$_server[$name];
    }

    /**
     * 设置服务端参数
     *
     * @param string $name   参数名称
     * @param mixed $value   参数值
     */
    public static function setServer($name, $value = NULL)
    {
        if (NULL == $value) {
            if (isset($_SERVER[$name])) {
                $value = $_SERVER[$name];
            } else if (isset($_ENV[$name])) {
                $value = $_ENV[$name];
            }
        }

        self::$_server[$name] = $value;
    }

    /**
     * 获取请求方式
     * 
     * @return boolean
     */
    public static function isMethods($method)
    {
        return strtolower($method) == strtolower(self::getServer('REQUEST_METHOD'));
    }

    /**
     * 解析请求参数
     * 
     * @access private
     */
    public static function parseRequest($key = null, $default = null)
    {
        if (self::isMethods('GET') || isset($_GET[$key])) {
            if (isset($_GET[$key])) {
                $data = @$_GET[$key];
            } else {
                $data = $default;
            }
        } elseif (self::isMethods('POST')) {
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                self::jsonData('Parse JSON error', [], false, 400);
            }
        }
        return $data;
    }

    /**
     * 获取 GET / POST 参数
     * 
     * @param string $key     参数的键
     * @param mixed $default  默认值
     * @return mixed
     */
    public static function getParams($key, $default = null)
    {
        if (self::isMethods('GET') || isset($_GET[$key])) {
            return self::parseRequest($key, $default);
        } else if (!empty(self::parseRequest()[$key])) {
            return self::parseRequest()[$key];
        } else {
            return $default;
        }
    }

    /**
     * 鉴权
     * 
     */
    public static function auth() {
        $token = !empty(self::getServer('HTTP_VOICE_ACCESS_TOKEN')) ? self::getServer('HTTP_VOICE_ACCESS_TOKEN') : self::getParams('access_token', null);
        if (!isset($token)) {
            self::jsonData('Unauthorized', [], false, 401);
        }

        if ($token != \tts\Config\Config::tmpGet()['access_token']) {
            self::jsonData('Unauthorized', [], false, 401);
        }
    }

    /**
     * 
     */
    public static function getResponse()
    {
        return [];
    }
}
