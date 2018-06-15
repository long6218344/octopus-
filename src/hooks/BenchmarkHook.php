<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：BenchmarkHook.php
 * 摘    要：性能统计钩子（例子）
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
class BenchmarkHook extends Hook
{

    private $marker = array();

    public function mark($pointer)
    {
        if (RUNMODE == 'development')
        {
            $this->marker[$pointer] = microtime(true);
        }
    }

    public function elapsedTime($pointer1, $pointer2)
    {
        if (RUNMODE == 'development')
        {
            echo "<div style=\"clear: both;display: block\">elapsed_time: " . number_format($this->marker[$pointer2] - $this->marker[$pointer1], 4) . "s</div>";
        }
    }

    public function memoryUsage()
    {
        if (RUNMODE == 'development')
        {
            echo "<div style=\"clear: both;display: block\">memory_usage: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB</div>";
        }
    }

}
