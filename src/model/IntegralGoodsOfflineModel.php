<?php


namespace integral\model;

class IntegralGoodsOfflineModel extends BaseModel
{
    protected $table = 'library.integral_goods_offline';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }

    /**
     * 一对多关联优惠券规则表
     * @return \think\model\relation\HasMany
     * @author chailiwei
     */
    public function couponRule()
    {
        return $this->hasMany(IntegralGoodsOfflineRuleModel::class, 'goods_offline_id', 'id');
    }
}