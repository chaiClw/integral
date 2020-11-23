<?php

namespace integral\service;


use integral\model\IntegralCategoryModel;
use integral\model\IntegralGoodsModel;
use integral\model\IntegralOrderModel;
use integral\model\MemberIntegralLogModel;
use integral\model\MemberIntegralModel;
use think\Db;
use think\db\Query;
use think\Exception;
use think\facade\Log;

class IntegralGoodsService
{
    public $integralGoodsModel;
    public $integralOrderModel;
    public $memberIntegralModel;
    public $memberIntegralLogModel;
    public $integralCategoryModel;

    public function __construct(
        IntegralGoodsModel $integralGoodsModel,
        IntegralOrderModel $integralOrderModel,
        MemberIntegralModel $memberIntegralModel,
        MemberIntegralLogModel $memberIntegralLogModel,
        IntegralCategoryModel $integralCategoryModel
    )
    {
        $this->integralGoodsModel = $integralGoodsModel;
        $this->integralOrderModel = $integralOrderModel;
        $this->memberIntegralModel = $memberIntegralModel;
        $this->memberIntegralLogModel = $memberIntegralLogModel;
        $this->integralCategoryModel = $integralCategoryModel;
    }

    /**
     * 积分兑换 --- 首页
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function home()
    {
        $where = [
            'goods_status' => 1,
            'is_del' => 0
        ];

        //实物商品列表展示
        $skuData = $this->integralGoodsModel->with([
            'integralGoodsSku' => function ($query) {
                $query->field('goods_id,price,stock,integral');
            }
        ])
            ->where(['goods_type' => 1])
            ->where($where)
            ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
            ->limit(6)->order('stock', 'desc')->select();
        foreach ($skuData as $key => $value) {
            $skuData[$key]['goods_img'] = $this->convertImageUrl($value['goods_img']);
            if (!empty($value['goods_head_img'])){
                $skuData[$key]['goods_head_img'] = $this->convertImageUrl(explode(',', $value['goods_head_img']));
            }
        }

        //到馆优惠券列表展示
        $offlineData = $this->integralGoodsModel->with([
            'integralGoodsOffline' => function ($query) {
                $query->field('goods_id,offline_type,lesson,man_lesson,appointment_limit,valid_days,expire_date');
            }
        ])
            ->where(['goods_type' => 2])
            ->where($where)
            ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
            ->limit(6)->order('stock', 'desc')->select();
        foreach ($offlineData as $key => $value) {
            $offlineData[$key]['goods_img'] = $this->convertImageUrl($value['goods_img']);
            if (!empty($value['goods_head_img'])){
                $offlineData[$key]['goods_head_img'] = $this->convertImageUrl(explode(',', $value['goods_head_img']));
            }
        }

        //在线优惠券列表展示
        $onlineData = $this->integralGoodsModel->with([
            'integralGoodsOnline' => function ($query) {
                $query->field('goods_id,online_type,days,times,price,man_price,valid_days');
            }
        ])
            ->where(['goods_type' => 3])
            ->where($where)
            ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
            ->limit(6)->order('stock', 'desc')->select();
        foreach ($onlineData as $key => $value) {
            $onlineData[$key]['goods_img'] = $this->convertImageUrl($value['goods_img']);
            if (!empty($value['goods_head_img'])){
                $onlineData[$key]['goods_head_img'] = $this->convertImageUrl(explode(',', $value['goods_head_img']));
            }
        }

        return ['sku_data' => $skuData, 'offline_data' => $offlineData, 'online_data' => $onlineData];
    }

    /**
     * 拼接图片完整地址
     * @param $image
     * @return array|string
     * @author chailiwei
     */
    public function convertImageUrl($image)
    {
        if (empty($image)) {
            return $image;
        }
        if (is_array($image)){
            $return = [];
            foreach ($image as $v){
                $return[] = Config("oss_url") . '/640x640/' . $v;
            }
            return $return;
        }
        return Config("oss_url") . '/640x640/' . $image;
    }

    /**
     * 积分兑换 --- 列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function index($param)
    {
        $goodsType = $param['goods_type'] ?? 1;
        $where = [
            'goods_status' => 1,
            'is_del' => 0
        ];
        $page_size = $param['page_size'] ?? 10;


        $orderType = $param['order_type'] ?? 1;
        if ($orderType == 2) {   //积分生序排序
            $order = 'integral asc,id desc';
        } else if ($orderType == 3) { //积分降序排序
            $order = 'integral desc,id desc';
        } else {
            $order = 'stock desc,id desc';
        }

        switch ($goodsType){
            case 1:  //实物
                $data = $this->integralGoodsModel->with([
                    'integralGoodsSku' => function ($query) {
                        $query->field('goods_id,price,stock,integral');
                    }
                ])
                    ->where(['goods_type' => 1])
                    ->where($where)
                    ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
                    ->order($order)
                    ->paginate($page_size,false);
                break;
            case 2:  //到馆
                $data = $this->integralGoodsModel->with([
                    'integralGoodsOffline' => function ($query) {
                        $query->field('goods_id,offline_type,lesson,man_lesson,appointment_limit,valid_days,expire_date');
                    }
                ])
                    ->where(['goods_type' => 2])
                    ->where($where)
                    ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
                    ->order($order)
                    ->paginate($page_size,false);
                break;
            case 3:  //在线
                $data = $this->integralGoodsModel->with([
                    'integralGoodsOffline' => function ($query) {
                        $query->field('goods_id,offline_type,lesson,man_lesson,appointment_limit,valid_days,expire_date');
                    }
                ])
                    ->where(['goods_type' => 2])
                    ->where($where)
                    ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
                    ->order($order)
                    ->paginate($page_size,false);
                break;
            default:
                $data = $this->integralGoodsModel->with([
                    'integralGoodsSku' => function ($query) {
                        $query->field('goods_id,price,stock,integral');
                    }
                ])
                    ->where(['goods_type' => 1])
                    ->where($where)
                    ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
                    ->order($order)
                    ->paginate($page_size,false);
        }

        foreach ($data as $key => $value) {
            $data[$key]['goods_img'] = $this->convertImageUrl($value['goods_img']);
//            if (!empty($value['goods_head_img'])){
//                $data[$key]['goods_head_img'] = $this->convertImageUrl(explode(',', $value['goods_head_img']));
//            }
        }

        return $data;
    }

    /**
     * 获取商品详情
     * @param $where
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    protected function goodsFind($where)
    {
        $integralGoodsData = $this->integralGoodsModel
            ->with([
                'integralGoodsSku' => function ($query) {
                    $query->field('goods_id,price,stock,integral');
                },
                'integralGoodsOffline' => function (Query $query) {
                    $query->field('goods_id,offline_type,lesson,man_lesson,appointment_limit,valid_days,expire_date');
                    $query->with([
                        'couponRule' => function (Query $query1) {
                            $query1->where(['is_del' => 0]);
                            $query1->field('goods_id,goods_offline_id,shopid,use_week,series_id,type_start,type_end');
                        }
                    ]);
                },
                'integralGoodsOnline' => function ($query) {
                    $query->field('goods_id,online_type,days,times,price,man_price,valid_days');
                }
            ])
            ->where($where)
            ->field('id,goods_type,goods_name,category_id,goods_img,goods_head_img,price,stock,integral,goods_title,goods_sub_title,goods_desc')
            ->find();
        if (!empty($integralGoodsData)) {
            $integralGoodsData->toArray();
        }
        return $integralGoodsData;
    }

    /**
     * 兑换商品详情页
     * @param $goodsId
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function detail($goodsId)
    {
        $where = [
            'goods_status' => 1,
            'is_del' => 0,
            'id' => $goodsId,
        ];

        $integralGoodsData = $this->goodsFind($where);

        if (!empty($integralGoodsData)) {
            $integralGoodsData['goods_img'] = $this->convertImageUrl($integralGoodsData['goods_img']);
            if (!empty($integralGoodsData['goods_head_img'])) {
                $integralGoodsData['goods_head_img'] = $this->convertImageUrl(explode(',', $integralGoodsData['goods_head_img']));
            }
        }

        return $integralGoodsData;
    }

    /**
     * 积分-兑换操作
     * @param $goodsId
     * @param $memberInfo
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function exchange($goodsId, $memberInfo, $param)
    {
        $where = [
            'goods_status' => 1,
            'is_del' => 0,
            'id' => $goodsId,
        ];

        $integralGoodsData = $this->goodsFind($where);
        if (empty($integralGoodsData)) {
            return ['status' => 0, 'msg' => '未查到商品数据'];
        }
        if ($integralGoodsData['stock'] == 0) {
            return ['status' => 0, 'msg' => '该商品太火爆了，已经被抢空了'];
        }

        $memberIntegralData = $this->memberIntegralModel->where([
            'entity_id' => $memberInfo['idmember']
        ])->find();

        //验证用户积分是否足够
        if ($memberIntegralData['integral'] < $integralGoodsData['integral']) {
            return ['status' => 0, 'msg' => '积分不足'];
        }

        $orderCategory = implode(',', $this->integralCategoryModel->whereIn('id', $integralGoodsData['category_id'])->column('category_name'));

        $orderData['order_sn'] = date('YmdHis') . mt_rand(10000, 99999);
        $orderData['member_integral_id'] = $memberIntegralData['id'];
        $orderData['member_nickname'] = $memberInfo['member_nickname'];
        $orderData['member_phone'] = $memberInfo['member_phone'];
        $orderData['goods_id'] = $goodsId;
        $orderData['order_name'] = $integralGoodsData['goods_name'];
        $orderData['order_type'] = $integralGoodsData['goods_type'];
        $orderData['order_category'] = $orderCategory;
        $orderData['order_price'] = $integralGoodsData['price'];
        $orderData['order_integral'] = $integralGoodsData['integral'];
        $orderData['order_status'] = 1;
        $orderData['order_time'] = date('Y-m-d H:i:s');
        $orderData['order_info'] = json_encode($integralGoodsData, JSON_UNESCAPED_UNICODE);


        if ($integralGoodsData['goods_type'] == 1) {
            //实物兑换需要收货地址
            $orderData['address_id'] = $param['address_id'] ?? 0;
            $orderData['address_realname'] = $param['address_realname'] ?? '';
            $orderData['address_phone'] = $param['address_phone'] ?? '';
            $orderData['address_province'] = $param['address_province'] ?? '';
            $orderData['address_city'] = $param['address_city'] ?? '';
            $orderData['address_area'] = $param['address_area'] ?? '';
            $orderData['address_detail'] = $param['address_detail'] ?? '';
        }

        Db::startTrans();
        try {
            //1、生成订单
            $this->integralOrderModel->insertGetId($orderData);
            //2、用户积分减少
            $currentIntegral = $memberIntegralData['integral'];
            $laterIntegral = $memberIntegralData['integral'] - $integralGoodsData['integral'];
            $memberIntegralData->save();

            //3、记录用户返还积分记录
            $logData = [
                'integral_id' => $memberIntegralData->id,
                'integral_title' => '兑换商品' . $integralGoodsData['goods_name'],
                'type' => 0,
                'integral_number' => '-' . $integralGoodsData['order_integral'],
                'original_integral' => $currentIntegral,
                'residue_integral' => $laterIntegral,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $this->memberIntegralLogModel->insert($logData);

            //4、商品兑换量 +1
            $this->integralGoodsModel->where([
                'id' => $integralGoodsData['goods_id']
            ])->inc('exchange_count');

            //同步数据
            if ($integralGoodsData['goods_type'] == 2) {
                //到馆优惠券同步
            } elseif ($integralGoodsData['goods_type'] == 3) {
                //在线优惠券同步
            }

            // 提交事务
            Db::commit();
            return ['status' => 200, 'msg' => '兑换成功'];
        } catch (Exception $exception) {
            Db::rollback();
            Log::record('兑换商品失败，失败原因：' . $exception->getMessage());
            return ['status' => 0, 'msg' => '兑换失败'];
        }
    }

    //兑换列表
    public function logIndex($type)
    {

    }

}