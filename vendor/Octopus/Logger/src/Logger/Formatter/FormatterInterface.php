<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：FormatterInterface.php
 * 摘    要：格式化接口
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Formatter;

interface FormatterInterface
{

    /**
     * 格式化操作
     * @param array $record
     */
    public function format(array $record);

    /**
     * 批量格式化操作
     * @param array $records
     */
    public function formatBatch(array $records);
}
