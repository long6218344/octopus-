<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：AbstractProcessingHandler.php
 * 摘    要：抽象处理操作类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Handler;

abstract class AbstractProcessingHandler extends AbstractHandler
{

    /**
     * 操作处理
     * @param array $record
     * @return boolean
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record))
        {
            return false;
        }
        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);
        $this->write($record);
        return false === $this->bubble;
    }

    /**
     * 写入记录
     */
    abstract protected function write(array $record);

    /**
     * 预处理记录
     * @param array $record
     * @return type
     */
    protected function processRecord(array $record)
    {
        if ($this->processors)
        {
            foreach ($this->processors as $processor)
            {
                $record = call_user_func($processor, $record);
            }
        }
        return $record;
    }

}
