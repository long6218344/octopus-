<?php

/**
 * redis配置文件
 */
$config['redis'] = array(
    'default' => array(
        'master' => array(
            'host' => '127.0.0.1',
            'port' => '6379'
        ),
        'slave' => array(
            'host' => 'xxx',
            'port' => '6379'
        ),
        'auth' => 'xxx'
    )
);