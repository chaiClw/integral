<?php
namespace integral\model;


class MemberIntegralModel extends BaseModel
{
    protected $table = 'library.member_integral';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}
