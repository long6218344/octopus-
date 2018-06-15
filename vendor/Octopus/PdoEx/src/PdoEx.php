<?php

/**
 * Copyright (c) 2015,上海二三四五网络科技股份有限公司
 * 文件名称：PdoEx.php
 * 摘    要：PDO封装类
 * 作    者：张小虎
 * 修改日期：2015.07.08
 */

namespace Octopus;

use PDO;
use Octopus\Logger\Registry;

class PdoEx
{

    private static $instances = array();
    private $dbname, $config, $dbW, $dbR;

    /**
     * 构造函数，设置配置
     * @param string $dbname
     * @param array $config
     */
    private function __construct($dbname, $config)
    {
        $this->dbname = $dbname;
        $this->config = $config;
    }

    /**
     * 获取单例实例
     * @param string $dbname
     * @param array $config
     * @return PdoEx
     */
    public static function getInstance($dbname, $config)
    {
        if (!isset(self::$instances[$dbname]))
        {
            self::$instances[$dbname] = new static($dbname, $config);
        }
        return self::$instances[$dbname];
    }

    /**
     * 删除单例实例
     * @param string $dbname
     */
    public static function delInstance($dbname)
    {
        if (self::$instances[$dbname])
        {
            self::$instances[$dbname]->dbW = null;
            self::$instances[$dbname]->dbR = null;
            self::$instances[$dbname] = null;
        }
    }

    /**
     * 获取可写db
     * @return PDO
     */
    public function getWritableDB()
    {
        if (!$this->dbW)
        {
            $dsn = 'mysql:host=' . $this->config['master']['host'] . ';port=' . $this->config['master']['port'] . ';dbname=' . $this->dbname . ';charset=' . $this->config['charset'];
            $username = $this->config['username'];
            $password = $this->config['password'];
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->config['charset'],
            );
            $this->dbW = new PDO($dsn, $username, $password, $options);
        }
        return $this->dbW;
    }

    /**
     * 获取可读db
     * @return PDO
     */
    public function getReadableDB()
    {
        if (!isset($this->config['slave']))
        {
            return $this->getWritableDB();
        }
        else
        {
            if (!$this->dbR)
            {
                if (array_keys($this->config['slave']) !== range(0, count($this->config['slave']) - 1))
                {
                    $slave = $this->config['slave'];
                }
                else
                {
                    $slave = $this->config['slave'][array_rand($this->config['slave'])];
                }
                $dsn = 'mysql:host=' . $slave['host'] . ';port=' . $slave['port'] . ';dbname=' . $this->dbname . ';charset=' . $this->config['charset'];
                $username = $this->config['username'];
                $password = $this->config['password'];
                $options = array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->config['charset'],
                );
                $this->dbR = new PDO($dsn, $username, $password, $options);
            }
            return $this->dbR;
        }
    }

    /**
     * 插入函数
     * @param string $table
     * @param array $data
     * @return boolean
     */
    public function insert($table, $data)
    {
        $db = $this->getWritableDB();
        $columns = array_keys($data);
        $stmt = $db->prepare("INSERT INTO $table (`" . implode("`, `", $columns) . "`) VALUES (:" . implode(", :", $columns) . ")");
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($data as $column => $param)
            {
                $search[] = ":$column";
                $replace[] = "'$param'";
                $stmt->bindValue(":$column", $param);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($data as $column => $param)
            {
                $stmt->bindValue(":$column", $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->rowCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * 更新函数
     * @param string $table
     * @param array $data
     * @param array $condition
     * @return boolean
     */
    public function update($table, $data, $condition)
    {
        $db = $this->getWritableDB();
        $columns = array_keys($data);
        foreach ($columns as $key => $column)
        {
            $columns[$key] = "`$column` = :$column";
        }
        $stmt = $db->prepare("UPDATE $table SET " . implode(',', $columns) . " WHERE {$condition['where']}");
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($data as $column => $param)
            {
                $search[] = ":$column";
                $replace[] = "'$param'";
                $stmt->bindValue(":$column", $param);
            }
            foreach ($condition['params'] as $column => $param)
            {
                $search[] = $column;
                $replace[] = "'$param'";
                $stmt->bindValue($column, $param);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($data as $column => $param)
            {
                $stmt->bindValue(":$column", $param);
            }
            foreach ($condition['params'] as $column => $param)
            {
                $stmt->bindValue($column, $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->rowCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * 删除函数
     * @param string $table
     * @param array $condition
     * @return boolean
     */
    public function delete($table, $condition)
    {
        $db = $this->getWritableDB();
        $stmt = $db->prepare("DELETE FROM $table WHERE {$condition['where']}");
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($condition['params'] as $column => $param)
            {
                $search[] = $column;
                $replace[] = "'$param'";
                $stmt->bindValue($column, $param);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($condition['params'] as $column => $param)
            {
                $stmt->bindValue($column, $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->rowCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * 批量插入函数
     * @param string $table
     * @param array $columns
     * @param array $data
     * @return boolean
     */
    public function batch($table, $columns, $data)
    {
        $db = $this->getWritableDB();
        $values = array();
        $bindValues = array();
        foreach ($data as $rowKey => $row)
        {
            $value = array();
            foreach ($columns as $colKey => $column)
            {
                $value[] = ":{$column}$rowKey";
                $bindValues[":{$column}$rowKey"] = $row[$colKey];
            }
            $values[] = "(" . implode(", ", $value) . ")";
        }
        $stmt = $db->prepare("INSERT INTO $table (`" . implode("`, `", $columns) . "`) VALUES " . implode(", ", $values));
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($bindValues as $bindColumn => $bindValue)
            {
                $search[] = $bindColumn;
                $replace[] = "'$bindValue'";
                $stmt->bindValue($bindColumn, $bindValue);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($bindValues as $bindColumn => $bindValue)
            {
                $stmt->bindValue($bindColumn, $bindValue);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->rowCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * 执行函数
     * @param string $sql
     * @param array $params
     * @param boolean $useReadableDB
     * @return boolean
     */
    public function query($sql, $params = array(), $useReadableDB = false)
    {
        if ($useReadableDB)
        {
            $db = $this->getReadableDB();
        }
        else
        {
            $db = $this->getWritableDB();
        }
        $stmt = $db->prepare($sql);
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($params as $column => $param)
            {
                $search[] = $column;
                $replace[] = "'$param'";
                $stmt->bindValue($column, $param);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($params as $column => $param)
            {
                $stmt->bindValue($column, $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->rowCount();
        }
        else
        {
            return false;
        }
    }

    /**
     * 查找单条记录
     * @param string $sql
     * @param array $params
     * @param boolean $useWritableDB
     * @return array|boolean
     */
    public function find($sql, $params = array(), $useWritableDB = false)
    {
        if ($useWritableDB)
        {
            $db = $this->getWritableDB();
        }
        else
        {
            $db = $this->getReadableDB();
        }
        $stmt = $db->prepare($sql);
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($params as $column => $param)
            {
                $search[] = $column;
                $replace[] = "'$param'";
                $stmt->bindValue($column, $param);
            }
            Registry::debug()->info(str_replace($search, $replace, $stmt->queryString));
        }
        else
        {
            foreach ($params as $column => $param)
            {
                $stmt->bindValue($column, $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    /**
     * 查找所有记录
     * @param string $sql
     * @param array $params
     * @param boolean $useWritableDB
     * @return array|boolean
     */
    public function findAll($sql, $params = array(), $useWritableDB = false)
    {
        if ($useWritableDB)
        {
            $db = $this->getWritableDB();
        }
        else
        {
            $db = $this->getReadableDB();
        }
        $stmt = $db->prepare($sql);
        if (defined('RUNMODE') && RUNMODE == 'development')
        {
            $search = array();
            $replace = array();
            foreach ($params as $column => $param)
            {
                $search[] = $column;
                $replace[] = "'$param'";
                $stmt->bindValue($column, $param);
            }
            Registry::debug()->info($stmt->queryString);
        }
        else
        {
            foreach ($params as $column => $param)
            {
                $stmt->bindValue($column, $param);
            }
        }
        if ($stmt->execute())
        {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    /**
     * 查找限定记录
     * @param string $sql
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @param boolean $useWritableDB
     * @return array|boolean
     */
    public function findList($sql, $params = array(), $limit = 0, $offset = 0, $useWritableDB = false)
    {
        if ($limit > 0)
        {
            if ($offset > 0)
            {
                $sql .= " LIMIT " . intval($offset) . "," . intval($limit);
            }
            else
            {
                $sql .= " LIMIT " . intval($limit);
            }
        }
        return $this->findAll($sql, $params, $useWritableDB);
    }

    /**
     * 获取最后插入的id
     * @return string
     */
    public function lastInsertId()
    {
        $db = $this->getWritableDB();
        return $db->lastInsertId();
    }

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        $db = $this->getWritableDB();
        $db->beginTransaction();
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        $db = $this->getWritableDB();
        $db->rollBack();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $db = $this->getWritableDB();
        $db->commit();
    }

}
