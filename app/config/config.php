<?php

/*
 * 基础配置文件
 */
define('RUNMODE', 'development');
$config = array(
    'modules' => array(
        'Demo' => array()
    ),
    'hooks' => array(
        'pre_bootstrap' => array(
            array(
                'class' => 'BenchmarkHook',
                'method' => 'mark',
                'params' => array(
                    'total_execution_time_start'
                )
            ),
        ),
        'post_bootstrap' => array(
            array(
                'class' => 'BenchmarkHook',
                'method' => 'mark',
                'params' => array(
                    'total_execution_time_end'
                )
            ),
            array(
                'class' => 'BenchmarkHook',
                'method' => 'elapsedTime',
                'params' => array(
                    'total_execution_time_start',
                    'total_execution_time_end'
                )
            ),
            array(
                'class' => 'BenchmarkHook',
                'method' => 'memoryUsage',
            ),
        ),
    )
);
include APPPATH . '/config/database.php';
include APPPATH . '/config/redis.php';
return $config;