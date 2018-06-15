<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：DefaultController.php
 * 摘    要：子系统（Demo）默认控制器（例子）
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */

namespace Demo;

use Controller;

class DefaultController extends Controller
{

    public function actionIndex()
    {
        echo "demo hello";
    }

}
