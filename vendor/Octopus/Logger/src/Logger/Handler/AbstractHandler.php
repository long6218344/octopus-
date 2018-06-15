<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：AbstractHandler.php
 * 摘    要：抽象操作句柄类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Handler;

use Octopus\Logger\Formatter\FormatterInterface;
use Octopus\Logger\Formatter\LineFormatter;
use Psr\Log\LogLevel;

abstract class AbstractHandler implements HandlerInterface
{

    protected $level = LogLevel::DEBUG;
    protected $bubble = true;
    protected $formatter;
    protected $processors = array();
    protected $weights = array(
        LogLevel::EMERGENCY => 800,
        LogLevel::ALERT => 700,
        LogLevel::CRITICAL => 600,
        LogLevel::ERROR => 500,
        LogLevel::WARNING => 400,
        LogLevel::NOTICE => 300,
        LogLevel::INFO => 200,
        LogLevel::DEBUG => 100,
    );

    /**
     * 构造函数，设置默认处理级别，是否冒泡
     * @param type $level
     * @param type $bubble
     */
    public function __construct($level = LogLevel::DEBUG, $bubble = true)
    {
        $this->setLevel($level);
        $this->bubble = $bubble;
    }

    /**
     * 判断是否可操作
     * @param array $record
     * @return type
     */
    public function isHandling(array $record)
    {
        return $this->weights[$record['level']] >= $this->weights[$this->level];
    }

    /**
     * 批量操作
     * @param array $records
     */
    public function handleBatch(array $records)
    {
        foreach ($records as $record)
        {
            $this->handle($record);
        }
    }

    /**
     * 关闭
     */
    public function close()
    {
        
    }

    /**
     * 推入预处理实例
     * @param type $callback
     * @return AbstractHandler
     * @throws \InvalidArgumentException
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback))
        {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), ' . var_export($callback, true) . ' given');
        }
        array_unshift($this->processors, $callback);
        return $this;
    }

    /**
     * 移出首位预处理实例
     * @return type
     * @throws \LogicException
     */
    public function popProcessor()
    {
        if (!$this->processors)
        {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }

    /**
     * 设置格式处理实例
     * @param FormatterInterface $formatter
     * @return AbstractHandler
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * 获取格式处理实例
     * @return type
     */
    public function getFormatter()
    {
        if (!$this->formatter)
        {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }

    /**
     * 设置可操作级别
     * @param type $level
     * @return AbstractHandler
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * 获取可操作级别
     * @return type
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * 设置是否冒泡
     * @param type $bubble
     * @return AbstractHandler
     */
    public function setBubble($bubble)
    {
        $this->bubble = $bubble;
        return $this;
    }

    /**
     * 获取是否冒泡
     * @return type
     */
    public function getBubble()
    {
        return $this->bubble;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        try
        {
            $this->close();
        }
        catch (\Exception $e)
        {
            // do nothing
        }
    }

    /**
     * 获取默认格式处理
     * @return LineFormatter
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }

}
