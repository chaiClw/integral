<?php
namespace integral\model;

use think\Model;

class IntegralGoodsModel extends Model
{
    protected $table = 'library.integral_goods';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }

    /**
     * 一对一关联实物商品
     * @return \think\model\relation\HasOne
     * @author chailiwei
     */
    public function integralGoodsSku()
    {
        return $this->hasOne(IntegralGoodsSkuModel::class, 'goods_id', 'id');
    }

    /**
     * 一对一关联在线优惠券
     * @return \think\model\relation\HasOne
     * @author chailiwei
     */
    public function integralGoodsOnline()
    {
        return $this->hasOne(IntegralGoodsOnlineModel::class, 'goods_id', 'id');
    }

    /**
     * 一对一关联到馆优惠券
     * @return \think\model\relation\HasOne
     * @author chailiwei
     */
    public function integralGoodsOffline()
    {
        return $this->hasOne(IntegralGoodsOfflineModel::class, 'goods_id', 'id');
    }
}
