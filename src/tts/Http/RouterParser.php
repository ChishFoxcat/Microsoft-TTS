<?php

namespace tts\Http;

class RouterParser
{
    /**
     * 默认规则表
     */
    private $_strRep;

    /**
     * 路由器映射表
     *
     * @access private
     * @var array
     */
    private $_table;

    /**
     * 初始化
     */
    public function __construct(array $table)
    {
        $this->_table = $table;

        $this->_strRep = [
            '[:uuid]'     =>  '([0-9a-fA-F]{8}(-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}+)',
            '[:rawUUID]'  =>  '([0-9a-fA-F]{8}([0-9a-fA-F]{4}){3}[0-9a-fA-F]{12}+)',
            '[:speaker]'  =>  '([a-zA-Z]{2,3}-[a-zA-Z]{2,3}-[a-zA-Z]{6,}+)'
        ];
    }

    /**
     * 变量替换
     *
     * @param  string  需替换的规则
     * @return string  替换后的规则
     * @access public
     */
    public function strRep($text)
    {
        foreach ($this->_strRep as $key => $regx) {
            $text = str_replace($key, $regx, $text);
        }

        return $text;
    }

    /**
     * 解析路由表
     * 
     */
    public function parser($regx = null)
    {
        $result = [];

        if ($regx !== NULL) {
            $this->_strRep = array_merge($regx, $this->_strRep);
        }

        foreach ($this->_table as $key => $route) {
            $route['regx'] = $this->strRep($route['path']);

            //处理斜线
            $route['regx'] = rtrim($route['regx'], '/');
            $route['regx'] = '|^' . $route['regx'] . '[/]?$|';

            $result[$key] = $route;
        }

        return $result;
    }
}
