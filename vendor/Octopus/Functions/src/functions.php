<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：functions.php
 * 摘    要：公用函数库
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
/**
 * 跳转
 * @param type $uri
 * @param type $http_response_code
 */
function redirect($uri = '', $http_response_code = 302)
{
    header("Location: " . $uri, TRUE, $http_response_code);
    exit;
}

/**
 * 关闭窗口
 */
function closeWindow()
{
    die('<script type="text/javascript">window.opener=null;window.open("", "_self", "");window.close();</script>');
}

/**
 * 获取用户IP
 * @param type $allowProxys
 * @return string
 */
function get_client_ip($allowProxys = array())
{
    if (getenv('REMOTE_ADDR'))
    {
        $onlineip = getenv('REMOTE_ADDR');
    }
    else
    {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    if (in_array($onlineip, $allowProxys))
    {
        if (getenv('HTTP_X_FORWARDED_FOR'))
        {
            $ips = getenv('HTTP_X_FORWARDED_FOR');
        }
        else if ($_SERVER['HTTP_X_FORWARDED_FOR'])
        {
            $ips = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if ($ips)
        {
            $ips = explode(",", $ips);
            $curIP = array_pop($ips);
            $onlineip = trim($curIP);
        }
    }
    if (filter_var($onlineip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
    {
        return $onlineip;
    }
    else
    {
        return '0.0.0.0';
    }
}

/**
 * curl获取内容
 * @param type $url
 * @param type $options
 * @return type
 */
function curl_get_contents($url, $options = array())
{
    $default = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; rv:17.0) Gecko/17.0 Firefox/17.0",
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 3,
    );
    foreach ($options as $key => $value)
    {
        $default[$key] = $value;
    }
    $ch = curl_init();
    curl_setopt_array($ch, $default);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * http get请求
 * @param type $url
 * @param type $params
 * @param type $options
 * @return type
 */
function http_get($url, $params = array(), $options = array())
{
    $paramsFMT = array();
    foreach ($params as $key => $val)
    {
        $paramsFMT[] = $key . "=" . urlencode($val);
    }
    return curl_get_contents($url . ($paramsFMT ? ( "?" . join("&", $paramsFMT)) : ""), $options);
}

/**
 * http post请求
 * @param type $url
 * @param type $params
 * @param type $options
 * @return type
 */
function http_post($url, $params = array(), $options = array())
{
    $paramsFMT = array();
    foreach ($params as $key => $val)
    {
        $paramsFMT[] = $key . "=" . urlencode($val);
    }
    $options[CURLOPT_POST] = 1;
    $options[CURLOPT_POSTFIELDS] = join("&", $paramsFMT);
    return curl_get_contents($url, $options);
}