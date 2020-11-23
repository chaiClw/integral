<?php

namespace integral\model;

class OfflineMemberModel extends BaseModel
{
    //连接数据表名
    protected $table = 'readyreading_xuehuiben.member';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}
