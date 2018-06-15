<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：StreamHandler.php
 * 摘    要：数据流操作类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Handler;

use Psr\Log\LogLevel;

class StreamHandler extends AbstractProcessingHandler
{

    protected $stream;
    protected $url;
    private $errorMessage;
    protected $filePermission;
    protected $useLocking;

    /**
     * 构造函数
     * @param type $stream
     * @param type $level
     * @param type $bubble
     * @param type $filePermission
     * @param type $useLocking
     * @throws \InvalidArgumentException
     */
    public function __construct($stream, $level = LogLevel::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream))
        {
            $this->stream = $stream;
        }
        elseif (is_string($stream))
        {
            $this->url = $stream;
        }
        else
        {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    /**
     * 关闭数据流
     */
    public function close()
    {
        if (is_resource($this->stream))
        {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * 写入数据流
     * @param array $record
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    protected function write(array $record)
    {
        if (!is_resource($this->stream))
        {
            if (!$this->url)
            {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->errorMessage = null;
            set_error_handler(array($this, 'customErrorHandler'));
            $this->stream = fopen($this->url, 'a');
            if ($this->filePermission !== null)
            {
                @chmod($this->url, $this->filePermission);
            }
            restore_error_handler();
            if (!is_resource($this->stream))
            {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $this->url));
            }
        }
        if ($this->useLocking)
        {
            // ignoring errors here, there's not much we can do about them
            flock($this->stream, LOCK_EX);
        }
        fwrite($this->stream, (string) $record['formatted']);
        if ($this->useLocking)
        {
            flock($this->stream, LOCK_UN);
        }
    }

    /**
     * 自定义错误操作
     * @param type $code
     * @param type $msg
     */
    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = preg_replace('{^fopen\(.*?\): }', '', $msg);
    }

}
