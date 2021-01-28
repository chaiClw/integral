<?php

namespace integral\model;

class DataMemberModel extends BaseModel
{
    //连接数据表名
    protected $table = 'library.data_member';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}
