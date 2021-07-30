<?php

namespace tts\Libs;

use ReflectionClass;
use ReflectionMethod;

use tts\Libs\Curl;

class Libs
{
    /**
     * 获取注解字段
     * 
     * @param  $doc
     * @return array
     */
    private static function parseInfo($doc): array
    {
        $tokens = token_get_all($doc);
        $isDoc = false;
        $isFunction = false;
        $isClass = false;
        $isInClass = false;
        $isInFunction = false;
        $isDefined = false;
        $current = NULL;

        $info = [
            'description' => ''
        ];

        foreach ($tokens as $token) {
            if (!$isDoc && is_array($token)) {
                $described = false;
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } else if ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);
                            $info[$key] = trim(implode(' ', $args));
                        }
                    }
                }
                $isDoc = true;
            }

            if (is_array($token)) {
                switch ($token[0]) {
                    case T_FUNCTION:
                        $isFunction = true;
                        break;
                    case T_IMPLEMENTS:
                        $isClass = true;
                        break;
                    case T_WHITESPACE:
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    case T_STRING:
                        $string = strtolower($token[1]);
                        switch ($string) {
                            case 'typecho_plugin_interface':
                                $isInClass = $isClass;
                                break;
                            case 'activate':
                            case 'deactivate':
                            case 'config':
                            case 'personalconfig':
                                if ($isFunction) {
                                    $current = ('personalconfig' == $string ? 'personalConfig' : $string);
                                }
                                break;
                            default:
                                if (!empty($current) && $isInFunction && $isInClass) {
                                    $info[$current] = true;
                                }
                                break;
                        }
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            } else {
                $token = strtolower($token);
                switch ($token) {
                    case '{':
                        if ($isDefined) {
                            $isInFunction = true;
                        }
                        break;
                    case '(':
                        if ($isFunction && !$isDefined) {
                            $isDefined = true;
                        }
                        break;
                    case '}':
                    case ';':
                        $isDefined = false;
                        $isFunction = false;
                        $isInFunction = false;
                        $current = NULL;
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            }
        }

        return $info;
    }

    /**
     * 获取路由规则
     * 
     * @param $class
     * @return array
     */
    public static function getRoutes($class, string $name = "path"): array
    {
        $routes = [];
        $ReflectionClass = new ReflectionClass(!empty($class) ? $class : __CLASS__);

        foreach ($ReflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            preg_match('/(.*)_Action$/', $method->getName(), $matches);

            if (!empty($matches[1])) {
                $parseInfo = self::parseInfo($method->getDocComment());
                $routes[] = [
                    'action' => $matches[0],
                    'name' => 'baka_restful_' . $matches[1],
                    'uri' => (!empty($parseInfo[$name])) ?  $parseInfo[$name] : $matches[1],
                    'description' => $parseInfo['description']
                ];
            }
        }

        return $routes;
    }

    /**
     * 获取令牌
     * 
     * @access private
     */
    public static function __getToken($file, $url, $toekn_key)
    {
        $header = [
            'Content-type: application/x-www-form-urlencoded',
            'Content-Length: 0',
            'Ocp-Apim-Subscription-Key: ' . $toekn_key
        ];

        $curl = Curl::post($url, [], $header);

        if (empty($curl)) {
            return false;
        }

        $token = [
            'last_update'  =>  time(),
            'token'        =>  $curl
        ];

        file_put_contents($file, json_encode($token));

        return $token;
    }

    /**
     * 创建 UUID
     *
     * @param $string
     * @return string UUID
     * @access public
     */
    public static function uuid($string, bool $divider = true)
    {
        $string = md5($string);
        $components = array(
            substr($string, 0, 8),
            substr($string, 8, 4),
            substr($string, 12, 4),
            substr($string, 16, 4),
            substr($string, 20),
        );

        return ($divider) ? implode('-', $components) : $components;
    }
}
