<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：Router.php
 * 摘    要：路由操作类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus;

class Router
{

    private static $class = '', $method = '', $params = array(), $config = array();

    /**
     * 路由初始化，设置自定义路由配置
     * @param type $config
     */
    public static function init($config)
    {
        static::$config = $config;
    }

    /**
     * 解析url
     * @return type
     */
    public static function parseUrl()
    {
        if (PHP_SAPI == 'cli')
        {
            static::parseUrlFromCli();
        }
        else
        {
            static::parseUrlFromWebServer();
        }
    }

    /**
     * 从命令行解析url
     */
    private static function parseUrlFromCli()
    {
        foreach ($_SERVER['argv'] as $key => $val)
        {
            if ($key == 0)
            {
                continue;
            }
            else if ($key == 1)
            {
                static::$class = $val;
            }
            else if ($key == 2)
            {
                static::$method = $val;
            }
            else
            {
                static::$params[] = $val;
            }
        }
    }

    /**
     * 从web服务器解析url
     */
    private static function parseUrlFromWebServer()
    {
        if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME']))
        {
            $uri = $_SERVER['REQUEST_URI'];
            $httpPre = (isset($_SERVER["HTTPS"]) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
            if (strpos($uri, $httpPre) === 0)
            {
                $uri = substr($uri, strlen($httpPre));
            }
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
            {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            }
            elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
            {
                $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
            $parts = preg_split('#\?#i', $uri, 2);
            $uri = $parts[0];
            if ($uri && $uri != '/')
            {
                $uri = parse_url($uri, PHP_URL_PATH);
                $uri = str_replace(array('//', '../'), '/', trim($uri, '/'));
                $uri = static::removeInvisibleCharacters($uri);
                //兼容.php后缀
                $uri = preg_replace("|.php$|", "", $uri);
                //兼容.php后缀
                $moves = isset(static::$config['moves']) ? static::$config['moves'] : '';
                if ($moves)
                {
                    if (isset($moves[$uri]))
                    {
                        redirect($moves[$uri], 'location', '301');
                    }
                }
                $routes = isset(static::$config['routes']) ? static::$config['routes'] : '';
                if ($routes)
                {
                    if (isset($routes[$uri]))
                    {
                        $uri = $routes[$uri];
                    }
                }
                if ($uri)
                {
                    if (!preg_match("|^[" . str_replace(array('\\-', '\-'), '-', preg_quote('a-z 0-9~%.:_\-/', '-')) . "]+$|i", $uri))
                    {
                        return;
                    }
                    $bad = array('$', '(', ')', '%28', '%29');
                    $good = array('&#36;', '&#40;', '&#41;', '&#40;', '&#41;');
                    $uri = str_replace($bad, $good, $uri);
                    $segments = explode('/', preg_replace("|/*(.+?)/*$|", "\\1", $uri));
                    $namespace = "";
                    $params = array();
                    foreach ($segments as $depth => $segment)
                    {
                        //添加匹配深度限制
                        if ($depth > 3 && static::$class == "")
                        {
                            return;
                        }
                        $prefix = $namespace . ucfirst(preg_replace_callback(
                                                '#((.)\_(.))#', create_function(
                                                        '$matches', 'return $matches[2] . strtoupper($matches[3]);'), $segment
                        ));
                        $namespace = $prefix . "\\";
                        $className = $prefix . "Controller";
                        if (class_exists($className))
                        {
                            static::$class = $className;
                            static::$method = "";
                            $params = array();
                            continue;
                        }
                        if (static::$class != "")
                        {
                            if (static::$method == "")
                            {
                                static::$method = "action" . ucfirst(preg_replace_callback(
                                                        '#((.)\_(.))#', create_function(
                                                                '$matches', 'return $matches[2] . strtoupper($matches[3]);'), $segment
                                ));
                                continue;
                            }
                            $params[] = $segment;
                        }
                    }
                    if (static::$method == "")
                    {
                        static::$method = "actionIndex";
                    }
                    else
                    {
                        static::$params = $params;
                    }
                }
            }
            else
            {
                static::$class = "DefaultController";
                static::$method = "actionIndex";
            }
        }
    }

    /**
     * 获取控制器类名
     * @return type
     */
    public static function fetchClass()
    {
        return static::$class;
    }

    /**
     * 获取控制器方法名
     * @return type
     */
    public static function fetchMethod()
    {
        return static::$method;
    }

    /**
     * 获取控制器参数
     * @return type
     */
    public static function fetchParams()
    {
        return static::$params;
    }

    /**
     * 过滤不合法字符
     * @param type $str
     * @param type $urlEncoded
     * @return type
     */
    private static function removeInvisibleCharacters($str, $urlEncoded = TRUE)
    {
        $nonDisplayables = array();
        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)
        if ($urlEncoded)
        {
            $nonDisplayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
            $nonDisplayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        }
        $nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127
        do
        {
            $str = preg_replace($nonDisplayables, '', $str, -1, $count);
        }
        while ($count);
        return $str;
    }

}
