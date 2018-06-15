<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：Octopus.php
 * 摘    要：框架工具类
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
class Octopus
{

    private static $hooks;

    public static function init()
    {
        static::$hooks = Config::get("hooks");
    }

    public static function callHook($type)
    {
        if (static::$hooks && isset(static::$hooks[$type]))
        {
            foreach (static::$hooks[$type] as $hook)
            {
                $class = $hook['class']::getInstance();
                $method = $hook['method'];
                $params = isset($hook['params']) ? $hook['params'] : array();
                call_user_func_array(array(&$class, $method), $params);
            }
        }
    }

}
