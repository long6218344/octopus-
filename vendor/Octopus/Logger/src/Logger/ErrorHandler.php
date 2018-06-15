<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：ErrorHandler.php
 * 摘    要：系统错误日志操作
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ErrorHandler
{

    private $logger;
    private $previousExceptionHandler;
    private $uncaughtExceptionLevel;
    private $previousErrorHandler;
    private $errorLevelMap;
    private $fatalLevel;
    private $reservedMemory;
    private static $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

    /**
     * 构造函数，设置日志实例
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 注册日志实例
     * @param \Psr\Log\LoggerInterface $logger
     * @param type $errorLevelMap
     * @param type $exceptionLevel
     * @param type $fatalLevel
     * @return \static
     */
    public static function register(LoggerInterface $logger, $errorLevelMap = array(), $exceptionLevel = null, $fatalLevel = null)
    {
        $handler = new static($logger);
        if ($errorLevelMap !== false)
        {
            $handler->registerErrorHandler($errorLevelMap);
        }
        if ($exceptionLevel !== false)
        {
            $handler->registerExceptionHandler($exceptionLevel);
        }
        if ($fatalLevel !== false)
        {
            $handler->registerFatalHandler($fatalLevel);
        }
        return $handler;
    }

    /**
     * 注册系统异常操作
     * @param type $level
     * @param type $callPrevious
     */
    public function registerExceptionHandler($level = null, $callPrevious = true)
    {
        $prev = set_exception_handler(array($this, 'handleException'));
        $this->uncaughtExceptionLevel = $level;
        if ($callPrevious && $prev)
        {
            $this->previousExceptionHandler = $prev;
        }
    }

    /**
     * 注册系统错误操作
     * @param array $levelMap
     * @param type $callPrevious
     * @param type $errorTypes
     */
    public function registerErrorHandler(array $levelMap = array(), $callPrevious = true, $errorTypes = -1)
    {
        $prev = set_error_handler(array($this, 'handleError'), $errorTypes);
        $this->errorLevelMap = array_replace($this->defaultErrorLevelMap(), $levelMap);
        if ($callPrevious)
        {
            $this->previousErrorHandler = $prev ? : true;
        }
    }

    /**
     * 注册系统致命操作
     * @param type $level
     * @param type $reservedMemorySize
     */
    public function registerFatalHandler($level = null, $reservedMemorySize = 20)
    {
        register_shutdown_function(array($this, 'handleFatalError'));

        $this->reservedMemory = str_repeat(' ', 1024 * $reservedMemorySize);
        $this->fatalLevel = $level;
    }

    /**
     * 默认系统错误级别映射
     * @return type
     */
    protected function defaultErrorLevelMap()
    {
        return array(
            E_ERROR => LogLevel::CRITICAL,
            E_WARNING => LogLevel::WARNING,
            E_PARSE => LogLevel::ALERT,
            E_NOTICE => LogLevel::NOTICE,
            E_CORE_ERROR => LogLevel::CRITICAL,
            E_CORE_WARNING => LogLevel::WARNING,
            E_COMPILE_ERROR => LogLevel::ALERT,
            E_COMPILE_WARNING => LogLevel::WARNING,
            E_USER_ERROR => LogLevel::ERROR,
            E_USER_WARNING => LogLevel::WARNING,
            E_USER_NOTICE => LogLevel::NOTICE,
            E_STRICT => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED => LogLevel::NOTICE,
            E_USER_DEPRECATED => LogLevel::NOTICE,
        );
    }

    /**
     * 操作系统异常信息
     */
    public function handleException(\Exception $e)
    {
        $this->logger->log(
                $this->uncaughtExceptionLevel === null ? LogLevel::ERROR : $this->uncaughtExceptionLevel, sprintf('Uncaught Exception %s: "%s" at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()), array('exception' => $e)
        );

        if ($this->previousExceptionHandler)
        {
            call_user_func($this->previousExceptionHandler, $e);
        }
    }

    /**
     * 操作系统错误信息
     * @param type $code
     * @param type $message
     * @param type $file
     * @param type $line
     * @param type $context
     * @return boolean
     */
    public function handleError($code, $message, $file = '', $line = 0, $context = array())
    {
        if (!(error_reporting() & $code))
        {
            return;
        }

        $level = isset($this->errorLevelMap[$code]) ? $this->errorLevelMap[$code] : LogLevel::CRITICAL;
        $this->logger->log($level, self::codeToString($code) . ': ' . $message, array('code' => $code, 'message' => $message, 'file' => $file, 'line' => $line));

        if ($this->previousErrorHandler === true)
        {
            return false;
        }
        elseif ($this->previousErrorHandler)
        {
            return call_user_func($this->previousErrorHandler, $code, $message, $file, $line, $context);
        }
    }

    /**
     * 操作系统致命信息
     */
    public function handleFatalError()
    {
        $this->reservedMemory = null;

        $lastError = error_get_last();
        if ($lastError && in_array($lastError['type'], self::$fatalErrors))
        {
            $this->logger->log(
                    $this->fatalLevel === null ? LogLevel::ALERT : $this->fatalLevel, 'Fatal Error (' . self::codeToString($lastError['type']) . '): ' . $lastError['message'], array('code' => $lastError['type'], 'message' => $lastError['message'], 'file' => $lastError['file'], 'line' => $lastError['line'])
            );
        }
    }

    /**
     * 系统错误代码字符串映射
     * @param type $code
     * @return string
     */
    private static function codeToString($code)
    {
        switch ($code)
        {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }
        return 'Unknown PHP error';
    }

}