<?php


namespace integral\model;

class IntegralGoodsOnlineModel extends BaseModel
{
    protected $table = 'library.integral_goods_online';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}