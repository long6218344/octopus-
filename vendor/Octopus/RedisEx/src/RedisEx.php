<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：RedisEx.php
 * 摘    要：redis封装类
 * 作    者：张小虎
 * 修改日期：2015.04.28
 */

namespace Octopus;

use \Redis;

class RedisEx
{

    /**
     * 可写方法名
     * @var type 
     */
    private static $writableMethods = array(
        'info' => 1,
        'append' => 1,
        'bitcount' => 1,
        'bitop' => 1,
        'decr' => 1,
        'decrBy' => 1,
        'getSet' => 1,
        'incr' => 1,
        'incrBy' => 1,
        'incrByFloat' => 1,
        'mSet' => 1,
        'mSetNX' => 1,
        'set' => 1,
        'setBit' => 1,
        'setex' => 1,
        'psetex' => 1,
        'setnx' => 1,
        'setRange' => 1,
        'del' => 1,
        'delete' => 1,
        'expire' => 1,
        'setTimeout' => 1,
        'pexpire' => 1,
        'expireAt' => 1,
        'pexpireAt' => 1,
        'migrate' => 1,
        'move' => 1,
        'persist' => 1,
        'rename' => 1,
        'renameKey' => 1,
        'renameNx' => 1,
        'sort' => 1,
        'ttl' => 1,
        'pttl' => 1,
        'restore' => 1,
        'hDel' => 1,
        'hIncrBy' => 1,
        'hIncrByFloat' => 1,
        'hMSet' => 1,
        'hSet' => 1,
        'hSetNx' => 1,
        'blPop' => 1,
        'brPop' => 1,
        'brpoplpush' => 1,
        'lInsert' => 1,
        'lPop' => 1,
        'lPush' => 1,
        'lPushx' => 1,
        'lRem' => 1,
        'lRemove' => 1,
        'lSet' => 1,
        'lTrim' => 1,
        'listTrim' => 1,
        'rPop' => 1,
        'rpoplpush' => 1,
        'rPush' => 1,
        'rPushx' => 1,
        'sAdd' => 1,
        'sDiffStore' => 1,
        'sInterStore' => 1,
        'sMove' => 1,
        'sPop' => 1,
        'sRem' => 1,
        'sRemove' => 1,
        'sUnionStore' => 1,
        'zAdd' => 1,
        'zIncrBy' => 1,
        'zInter' => 1,
        'zRem' => 1,
        'zDelete' => 1,
        'zRemRangeByRank' => 1,
        'zDeleteRangeByRank' => 1,
        'zRemRangeByScore' => 1,
        'zDeleteRangeByScore' => 1,
        'zUnion' => 1,
    );
    /**
     * 可读方法名
     * @var type 
     */
    private static $readableMethods = array(
        'get' => 1,
        'getBit' => 1,
        'getRange' => 1,
        'mGet' => 1,
        'getMultiple' => 1,
        'strlen' => 1,
        'dump' => 1,
        'exists' => 1,
        'keys' => 1,
        'getKeys' => 1,
        'object' => 1,
        'randomKey' => 1,
        'type' => 1,
        'hExists' => 1,
        'hGet' => 1,
        'hGetAll' => 1,
        'hKeys' => 1,
        'hLen' => 1,
        'hMGet' => 1,
        'hVals' => 1,
        'lIndex' => 1,
        'lGet' => 1,
        'lLen' => 1,
        'lSize' => 1,
        'lRange' => 1,
        'lGetRange' => 1,
        'sCard' => 1,
        'sSize' => 1,
        'sDiff' => 1,
        'sInter' => 1,
        'sIsMember' => 1,
        'sContains' => 1,
        'sMembers' => 1,
        'sGetMembers' => 1,
        'sRandMember' => 1,
        'sUnion' => 1,
        'zCard' => 1,
        'zSize' => 1,
        'zCount' => 1,
        'zRange' => 1,
        'zRangeByScore' => 1,
        'zRevRangeByScore' => 1,
        'zRank' => 1,
        'zRevRank' => 1,
        'zRevRange' => 1,
        'zScore' => 1,
    );
    private static $instances = array();
    private $config, $redisW, $redisR;

    /**
     * 构造函数，设置配置
     * @param type $config
     */
    private function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 获取单例实例
     * @param type $name
     * @param type $config
     * @return type
     */
    public static function getInstance($name, $config)
    {
        if (!isset(self::$instances[$name]) || !self::$instances[$name])
        {
            self::$instances[$name] = new static($config);
        }
        return self::$instances[$name];
    }

    /**
     * 删除单例实例
     * @param type $name
     */
    public static function delInstance($name)
    {
        if (self::$instances[$name])
        {
            self::$instances[$name] = null;
        }
    }

    /**
     * 获取可写redis实例
     * @return type
     */
    public function getWritableRedis()
    {
        if (!$this->redisW)
        {
            $this->redisW = new Redis();
            $this->redisW->connect($this->config['master']['host'], $this->config['master']['port'], 5);
            $this->redisW->auth($this->config['auth']);
        }
        return $this->redisW;
    }

    /**
     * 获取可读redis实例
     * @return type
     */
    public function getReadableRedis()
    {
        if (!isset($this->config['slave']))
        {
            return $this->getWritableRedis();
        }
        else
        {
            if (!$this->redisR)
            {
                if (array_keys($this->config['slave']) !== range(0, count($this->config['slave']) - 1))
                {
                    $slave = $this->config['slave'];
                }
                else
                {
                    $slave = $this->config['slave'][array_rand($this->config['slave'])];
                }
                $this->redisR = new Redis();
                $this->redisR->connect($slave['host'], $slave['port'], 5);
                $this->redisR->auth($this->config['auth']);
            }
            return $this->redisR;
        }
    }

    /**
     * 魔术方法，调用redis方法
     * @param type $name
     * @param type $arguments
     * @return boolean
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$writableMethods[$name]))
        {
            $redis = $this->getWritableRedis();
        }
        else if (isset(static::$readableMethods[$name]))
        {
            $redis = $this->getReadableRedis();
        }
        else
        {
            return false;
        }
        return call_user_func_array(array(&$redis, $name), $arguments);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        if ($this->redisW)
        {
            $this->redisW->close();
        }
        if ($this->redisR)
        {
            $this->redisR->close();
        }
    }

}