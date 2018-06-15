<?php

/**
 * 公用函数文件
 */

/**
 * 显示错误页面
 * @param type $code
 * @param type $statusCode
 * @param type $desc
 */
function showError($code, $statusCode = 500, $desc = '')
{
    header("HTTP/1.1 $statusCode");
    loadView('error.tpl.html', array('error' => $code, 'desc' => $desc));
    exit;
}

/**
 * 显示404页面
 */
function show404()
{
    header("HTTP/1.1 404");
    loadView('error.tpl.html', array('error' => 404));
    exit;
}

/**
 * 加载视图
 * @staticvar Smarty $smarty
 * @param type $tpl
 * @param type $array
 * @param type $return
 * @return type
 */
function loadView($tpl, $array = array(), $return = false)
{
    static $smarty;
    if (!$smarty)
    {
        $smarty = new Smarty();
        if (!defined("VIEWPATH"))
        {
            define("VIEWPATH", SRCPATH . '/views');
        }
        $smarty->template_dir = VIEWPATH . '/tpl/';
        $smarty->compile_dir = VIEWPATH . '/tpl_c/';
        $smarty->left_delimiter = '{{';
        $smarty->right_delimiter = '}}';
    }
    $smarty->assign('pageArray', $array);
    if ($return)
    {
        return $smarty->fetch($tpl);
    }
    else
    {
        $smarty->display($tpl);
    }
}
