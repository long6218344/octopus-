<?php

/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：Model.php
 * 摘    要：Model基类
 * 作    者：张小虎
 * 修改日期：2013.10.12
 */
use Octopus\PdoEx;

class Model extends BaseClass
{

    protected $pdo, $dbName, $tableName, $pkName, $attrs, $id, $data = array();

    public function __construct()
    {
        if ($this->dbName)
        {
            $dbConfig = Config::get("database");
            $this->pdo = PdoEx::getInstance($this->dbName, $dbConfig[$this->dbName]);
        }
    }

    public function save()
    {
        return $this->edit($this->id, $this->data);
    }

    public function drop()
    {
        return $this->del($this->id);
    }

    public function get($id)
    {
        return $this->pdo->find("SELECT * FROM " . $this->tableName . " WHERE " . $this->pkName . " = :id", array(":id" => $id));
    }

    public function add($data)
    {
        return $this->pdo->insert($this->tableName, $data);
    }

    public function edit($id, $data)
    {
        $condition = array(
            "where" => $this->pkName . " = :id",
            "params" => array(":id" => $id)
        );
        return $this->pdo->update($this->tableName, $data, $condition);
    }

    public function del($id)
    {
        $condition = array(
            "where" => $this->pkName . " = :id",
            "params" => array(":id" => $id)
        );
        return $this->pdo->delete($this->tableName, $condition);
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

}