<?php

namespace tts\Http;

use tts\Http\RouterParser;

class Router {
    private static $_route_table = [];

    /**
     * 添加路由
     * 
     * @param string $name   路由名称
     * @param string $path   路由路径
     * @param string $widget 组件名称
     * @param string $action 组件动作
     */
    public static function add($name, $path, $widget, $action) {
        $table = self::$_route_table;

        foreach($table as $route) {
            if ($route['name'] == $name) {
                $hasRoute = true;
                break;
            }
        }

        if (isset($hasRoute) && @$hasRoute === true) return false;

        self::$_route_table[] = [
            'name'    => $name,
            'path'    => $path,
            'widget'  => $widget,
            'action'  => $action,
        ];
    }

    /**
     * 获取路由列表
     * 
     * @return array
     */
    public static function getRouteTable() : array {
        return self::$_route_table;
    }

    /**
     * 获取解析后的路由表
     * 
     * @return array
     */
    public static function getRoutePaserTable() : array {
        $parser = new RouterParser(self::getRouteTable());
        $route = $parser->parser();                   

        return $route;
    }
}