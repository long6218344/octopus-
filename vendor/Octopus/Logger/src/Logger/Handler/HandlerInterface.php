<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：HandlerInterface.php
 * 摘    要：操作句柄接口
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Handler;

use Octopus\Logger\Formatter\FormatterInterface;

interface HandlerInterface
{

    /**
     * 是否可操作
     * @param array $record
     */
    public function isHandling(array $record);

    /**
     * 操作处理
     * @param array $record
     */
    public function handle(array $record);

    /**
     * 批量操作
     * @param array $records
     */
    public function handleBatch(array $records);

    /**
     * 推入预处理实例
     * @param type $callback
     */
    public function pushProcessor($callback);

    /**
     * 移出首位预处理实例
     */
    public function popProcessor();

    /**
     * 设置格式化处理实例
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter);

    /**
     * 获取格式化处理实例
     */
    public function getFormatter();
}
