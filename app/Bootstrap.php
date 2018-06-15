<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：Bootstrap.php
 * 摘    要：MVC框架启动文件
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
$loader = require APPPATH . '/../vendor/autoload.php';

use Octopus\Router;
use Octopus\Logger\Handler\StreamHandler;
use Octopus\Logger\ErrorHandler;
use Octopus\Logger\Registry;
use Octopus\Logger;

Config::load(APPPATH . '/config/config.php');
if (RUNMODE == 'development')
{
    $logger = new Logger("debug");
    $logger->pushHandler(new StreamHandler(APPPATH . "/logs/debug.log"));
    ErrorHandler::register($logger);
    Registry::addLogger($logger);
}
$modules = Config::get("modules");
if ($modules)
{
    $moduleNames = array_keys($modules);
    foreach ($moduleNames as $moduleName)
    {
        $loader->setPsr4("$moduleName\\", array(
            SRCPATH . "/modules/$moduleName/actions",
            SRCPATH . "/modules/$moduleName/controllers",
            SRCPATH . "/modules/$moduleName/models"
        ));
    }
}
Octopus::init();
Octopus::callHook('pre_bootstrap');
Router::parseUrl();
Octopus::callHook('cache_override');
$className = Router::fetchClass();
$methodName = Router::fetchMethod();
$params = Router::fetchParams();
if ($className == "" || !class_exists($className))
{
    show404();
}
else
{
    if (!in_array($methodName, get_class_methods($className)))
    {
        show404();
    }
    else
    {
        $reflectionMethod = new ReflectionMethod($className, $methodName);
        if (!$reflectionMethod->isPublic() || $reflectionMethod->isStatic())
        {
            show404();
        }
        else
        {
            Octopus::callHook('pre_controller');
            define("VIEWPATH", preg_replace("/\\" . DIRECTORY_SEPARATOR . "controllers\\" . DIRECTORY_SEPARATOR . ".*/", DIRECTORY_SEPARATOR . "views", realpath($loader->findFile($className))));
            $class = $className::getInstance();
            Octopus::callHook('post_controller_constructor');
            call_user_func_array(array(&$class, $methodName), $params);
            Octopus::callHook('post_controller');
        }
    }
}
Octopus::callHook('post_bootstrap');
