<?php


namespace integral\model;

class IntegralGoodsOfflineRuleModel extends BaseModel
{
    public $table = 'library.integral_goods_offline_rule';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}