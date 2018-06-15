<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：NormalizerFormatter.php
 * 摘    要：标准格式化类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Formatter;

use Exception;

class NormalizerFormatter implements FormatterInterface
{

    const SIMPLE_DATE = "Y-m-d H:i:s";

    protected $dateFormat;

    /**
     * 构造函数，定义日期模板
     * @param type $dateFormat
     * @throws \RuntimeException
     */
    public function __construct($dateFormat = null)
    {
        $this->dateFormat = $dateFormat ? : static::SIMPLE_DATE;
        if (!function_exists('json_encode'))
        {
            throw new \RuntimeException('PHP\'s json extension is required to use Logger\'s NormalizerFormatter');
        }
    }

    /**
     * 格式化操作
     * @param array $record
     * @return type
     */
    public function format(array $record)
    {
        return $this->normalize($record);
    }

    /**
     * 批量格式化操作
     * @param array $records
     * @return type
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record)
        {
            $records[$key] = $this->format($record);
        }
        return $records;
    }

    /**
     * 标准化处理
     * @param type $data
     * @return string
     */
    protected function normalize($data)
    {
        if (null === $data || is_scalar($data))
        {
            if (is_float($data))
            {
                if (is_infinite($data))
                {
                    return ($data > 0 ? '' : '-') . 'INF';
                }
                if (is_nan($data))
                {
                    return 'NaN';
                }
            }
            return $data;
        }
        if (is_array($data) || $data instanceof \Traversable)
        {
            $normalized = array();
            $count = 1;
            foreach ($data as $key => $value)
            {
                if ($count++ >= 1000)
                {
                    $normalized['...'] = 'Over 1000 items, aborting normalization';
                    break;
                }
                $normalized[$key] = $this->normalize($value);
            }
            return $normalized;
        }
        if ($data instanceof \DateTime)
        {
            return $data->format($this->dateFormat);
        }
        if (is_object($data))
        {
            if ($data instanceof Exception)
            {
                return $this->normalizeException($data);
            }
            return sprintf("[object] (%s: %s)", get_class($data), $this->toJson($data, true));
        }
        if (is_resource($data))
        {
            return '[resource]';
        }
        return '[unknown(' . gettype($data) . ')]';
    }

    /**
     * 定义标准化异常处理
     * @param Exception $e
     * @return type
     */
    protected function normalizeException(Exception $e)
    {
        $data = array(
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        );
        $trace = $e->getTrace();
        foreach ($trace as $frame)
        {
            if (isset($frame['file']))
            {
                $data['trace'][] = $frame['file'] . ':' . $frame['line'];
            }
            else
            {
                // We should again normalize the frames, because it might contain invalid items
                $data['trace'][] = $this->toJson($this->normalize($frame), true);
            }
        }
        if ($previous = $e->getPrevious())
        {
            $data['previous'] = $this->normalizeException($previous);
        }
        return $data;
    }

    /**
     * json转换
     * @param type $data
     * @param type $ignoreErrors
     * @return type
     */
    protected function toJson($data, $ignoreErrors = false)
    {
        // suppress json_encode errors since it's twitchy with some inputs
        if ($ignoreErrors)
        {
            if (version_compare(PHP_VERSION, '5.4.0', '>='))
            {
                return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            return @json_encode($data);
        }
        if (version_compare(PHP_VERSION, '5.4.0', '>='))
        {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return json_encode($data);
    }

}
