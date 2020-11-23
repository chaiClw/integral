<?php
namespace integral\model;

class IntegralGoodsSkuModel extends BaseModel
{
    protected $table = 'library.integral_goods_sku';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}
