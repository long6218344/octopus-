<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：index.php
 * 摘    要：MVC框架入口文件
 * 作    者：张小虎
 * 修改日期：2015.02.11
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');
define('BASEPATH', __DIR__);
define('APPPATH', realpath(BASEPATH . '/../app'));
define('SRCPATH', realpath(BASEPATH . '/../src'));
include APPPATH . '/Bootstrap.php';
