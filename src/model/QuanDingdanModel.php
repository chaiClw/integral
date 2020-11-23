<?php

namespace integral\model;

class QuanDingdanModel extends BaseModel
{
    protected $pk = 'iddingdanquan';
    //连接数据表名
    protected $table = 'readyreading_xuehuiben.quan_dingdan';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}