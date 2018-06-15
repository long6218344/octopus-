<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：Registry.php
 * 摘    要：日志注册工具类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger;

use Psr\Log\LoggerInterface;

class Registry
{

    private static $loggers = array();

    /**
     * 添加日志实例
     * @param LoggerInterface $logger
     * @param type $name
     * @param type $overwrite
     * @throws \InvalidArgumentException
     */
    public static function addLogger(LoggerInterface $logger, $name = null, $overwrite = false)
    {
        $name = $name ? : $logger->getName();

        if (isset(self::$loggers[$name]) && !$overwrite)
        {
            throw new \InvalidArgumentException('Logger with the given name already exists');
        }

        self::$loggers[$name] = $logger;
    }

    /**
     * 移除日志实例
     * @param LoggerInterface $logger
     */
    public static function removeLogger(LoggerInterface $logger)
    {
        if ($logger instanceof Logger)
        {
            if (false !== ($idx = array_search($logger, self::$loggers, true)))
            {
                unset(self::$loggers[$idx]);
            }
        }
        else
        {
            unset(self::$loggers[$logger]);
        }
    }

    /**
     * 清空日志实例
     */
    public static function clear()
    {
        self::$loggers = array();
    }

    /**
     * 获取某个日志实例
     * @param type $name
     * @return type
     * @throws \InvalidArgumentException
     */
    public static function getInstance($name)
    {
        if (!isset(self::$loggers[$name]))
        {
            throw new \InvalidArgumentException(sprintf('Requested "%s" logger instance is not in the registry', $name));
        }
        return self::$loggers[$name];
    }

    /**
     * 魔术方法，获取某个日志实例
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getInstance($name);
    }

}
