<?php

namespace integral\model;

class MemberIntegralLogModel extends BaseModel
{
    protected $table = 'library.member_integral_log';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}