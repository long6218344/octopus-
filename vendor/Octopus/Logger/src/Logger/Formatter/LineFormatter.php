<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：LineFormatter.php
 * 摘    要：一行格式化类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus\Logger\Formatter;

use Exception;

class LineFormatter extends NormalizerFormatter
{

    const SIMPLE_FORMAT = "[%datetime%] %channel%.%level%: %message% %context% %extra%\n";

    protected $format;
    protected $allowInlineLineBreaks;
    protected $ignoreEmptyContextAndExtra;
    protected $includeStacktraces;

    /**
     * 构造函数
     * @param type $format
     * @param type $dateFormat
     * @param type $allowInlineLineBreaks
     * @param type $ignoreEmptyContextAndExtra
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        $this->format = $format ? : static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        parent::__construct($dateFormat);
    }

    /**
     * 设置引入堆栈跟踪
     * @param type $include
     */
    public function includeStacktraces($include = true)
    {
        $this->includeStacktraces = $include;
        if ($this->includeStacktraces)
        {
            $this->allowInlineLineBreaks = true;
        }
    }

    /**
     * 设置是否允许断行
     * @param type $allow
     */
    public function allowInlineLineBreaks($allow = true)
    {
        $this->allowInlineLineBreaks = $allow;
    }

    /**
     * 设置是否忽略空内容
     * @param type $ignore
     */
    public function ignoreEmptyContextAndExtra($ignore = true)
    {
        $this->ignoreEmptyContextAndExtra = $ignore;
    }

    /**
     * 格式化操作
     * @param array $record
     * @return type
     */
    public function format(array $record)
    {
        $vars = parent::format($record);
        $output = $this->format;
        foreach ($vars['extra'] as $var => $val)
        {
            if (false !== strpos($output, '%extra.' . $var . '%'))
            {
                $output = str_replace('%extra.' . $var . '%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }
        if ($this->ignoreEmptyContextAndExtra)
        {
            if (empty($vars['context']))
            {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }
            if (empty($vars['extra']))
            {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }
        foreach ($vars as $var => $val)
        {
            if (false !== strpos($output, '%' . $var . '%'))
            {
                $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
            }
        }
        return $output;
    }

    /**
     * 批量格式化操作
     * @param array $records
     * @return type
     */
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record)
        {
            $message .= $this->format($record);
        }

        return $message;
    }

    /**
     * 字符串转换自定义
     * @param type $value
     * @return type
     */
    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    /**
     * 定义标准化异常处理
     * @param Exception $e
     * @return string
     */
    protected function normalizeException(Exception $e)
    {
        $previousText = '';
        if ($previous = $e->getPrevious())
        {
            do
            {
                $previousText .= ', ' . get_class($previous) . '(code: ' . $previous->getCode() . '): ' . $previous->getMessage() . ' at ' . $previous->getFile() . ':' . $previous->getLine();
            }
            while ($previous = $previous->getPrevious());
        }
        $str = '[object] (' . get_class($e) . '(code: ' . $e->getCode() . '): ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . $previousText . ')';
        if ($this->includeStacktraces)
        {
            $str .= "\n[stacktrace]\n" . $e->getTraceAsString();
        }
        return $str;
    }

    /**
     * 转换成字符串
     * @param type $data
     * @return type
     */
    protected function convertToString($data)
    {
        if (null === $data || is_bool($data))
        {
            return var_export($data, true);
        }
        if (is_scalar($data))
        {
            return (string) $data;
        }
        if (version_compare(PHP_VERSION, '5.4.0', '>='))
        {
            return $this->toJson($data, true);
        }
        return str_replace('\\/', '/', @json_encode($data));
    }

    /**
     * 替换新行
     * @param type $str
     * @return type
     */
    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks)
        {
            return $str;
        }
        return strtr($str, array("\r\n" => ' ', "\r" => ' ', "\n" => ' '));
    }

}
