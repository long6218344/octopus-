<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：DefaultController.php
 * 摘    要：默认控制器
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
class DefaultController extends Controller
{

    public function actionIndex()
    {
        $defaultModel = DefaultModel::getInstance();
        $hello = $defaultModel->hello();
        loadView("index.tpl.html", array("hello" => $hello));
    }
	
	/**
	 * 测试Redis
	 */
	public function actionRedisTest()
	{
		$arrConfig = Config::get('redis');
		$objRedis = Octopus\RedisEx::getInstance('master', $arrConfig['default']);
		$objRedisW = $objRedis->getWritableRedis();
		// $objRedisW->set('test:a', '21211');
		echo $objRedisW->get('test:a');
	}

}
