<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：BaseClass.php
 * 摘    要：基类
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
class BaseClass
{

    protected static $instances = array();

    private function __construct()
    {
        
    }

    public static function getInstance()
    {
        $className = get_called_class();
        if (!isset(static::$instances[$className]))
        {
            static::$instances[$className] = new static();
        }
        return static::$instances[$className];
    }

}
