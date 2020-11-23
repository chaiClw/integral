<?php

namespace integral\service;


use integral\model\CaozuoLogModel;
use integral\model\CouponRuleModel;
use integral\model\IntegralCategoryModel;
use integral\model\IntegralGoodsModel;
use integral\model\IntegralOrderModel;
use integral\model\MemberHasQuan;
use integral\model\MemberIntegralLogModel;
use integral\model\MemberIntegralModel;
use integral\model\OfflineMemberModel;
use integral\model\OnlineMemberModel;
use integral\model\QuanDingdanModel;
use integral\model\QuanLogModel;
use integral\model\RankQuanModel;
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
    public $rankQuanModel;

    public function __construct(
        IntegralGoodsModel $integralGoodsModel,
        IntegralOrderModel $integralOrderModel,
        MemberIntegralModel $memberIntegralModel,
        MemberIntegralLogModel $memberIntegralLogModel,
        IntegralCategoryModel $integralCategoryModel,
        RankQuanModel $rankQuanModel
    )
    {
        $this->integralGoodsModel = $integralGoodsModel;
        $this->integralOrderModel = $integralOrderModel;
        $this->memberIntegralModel = $memberIntegralModel;
        $this->memberIntegralLogModel = $memberIntegralLogModel;
        $this->integralCategoryModel = $integralCategoryModel;
        $this->rankQuanModel = $rankQuanModel;
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
            if (!empty($value['goods_head_img'])) {
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
            if (!empty($value['goods_head_img'])) {
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
            if (!empty($value['goods_head_img'])) {
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
        if (is_array($image)) {
            $return = [];
            foreach ($image as $v) {
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

        switch ($goodsType) {
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
                    ->paginate($page_size, false);
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
                    ->paginate($page_size, false);
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
                    ->paginate($page_size, false);
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
                    ->paginate($page_size, false);
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
     * @param $userType (借阅-1 到馆-2)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function exchange($goodsId, $memberInfo, $param, $userType = 1)
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

        if ($userType == 1) {
            $memberIntegralData = $this->memberIntegralModel->where([
                'online_id' => $memberInfo['idmember']
            ])->find();
        } else {
            $memberIntegralData = $this->memberIntegralModel->where([
                'entity_id' => $memberInfo['idmember']
            ])->find();
        }

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
                $memberOfflineId = $memberIntegralData->entity_id;
                if (!empty($memberOfflineId)) {
                    $memberInfo = OfflineMemberModel::where(['idmember' => $memberOfflineId])->find();
                    $this->syncOfflineCoupon($integralGoodsData, $memberInfo);
                }
            } elseif ($integralGoodsData['goods_type'] == 3) {
                //在线优惠券同步
                $memberOnlineId = $memberIntegralData->online_id;
                if (!empty($memberOnlineId)) {
                    $memberInfo = OnlineMemberModel::where(['idmember' => $memberOnlineId])->find();
                    $this->syncOnlineCoupon($integralGoodsData, $memberInfo);
                }
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


    /**
     * 同步在线优惠券
     * @param $integralGoodsData
     * @param $memberInfo
     * @throws Exception
     * @throws \think\db\exception\DbException
     * @throws \think\exception\PDOException
     * @author chailiwei
     */
    public function syncOnlineCoupon($integralGoodsData, $memberInfo)
    {
        $onlineType = $integralGoodsData['integral_goods_online']['online_type'];

        $indate['quan_type'] = $onlineType;
        if ($onlineType == 1) {
            $quan_number = 'SCQ' . date('d') . strtoupper($this->getRandChar(8));
            $indate['quan_tianshu'] = $integralGoodsData['integral_goods_online']['days'];
        } elseif ($onlineType == 2) {
            $quan_number = 'CSQ' . date('d') . strtoupper($this->getRandChar(8));
            $indate['quan_cishu'] = $integralGoodsData['integral_goods_online']['times'];
        } elseif ($onlineType == 3) {
            $quan_number = 'JEQ' . date('d') . strtoupper($this->getRandChar(8));
            $indate['quan_price'] = $integralGoodsData['integral_goods_online']['price'];
            $indate['man_price'] = $integralGoodsData['integral_goods_online']['man_price'];
            $indate['rankid'] = 0;
        }
        $validDays = $integralGoodsData['integral_goods_online']['valid_days'];
        $indate['quan_number'] = $quan_number;
        $indate['quan_status'] = 3;
        $indate['lingqu_time'] = date('Y-m-d H:i:s');
        $indate['start_date'] = date('Y-m-d');
        $indate['end_date'] = strtotime("+ $validDays day");
        $indate['create_time'] = date('Y-m-d H:i:s');
        $indate['memberid'] = $memberInfo['idmember'];
        $indate['member_phone'] = $memberInfo['member_phone'];
        $indate['member_nickname'] = $memberInfo['member_nickname'];
        $indate['member_realname'] = $memberInfo['member_realname'];
        $indate['member_touxiang'] = $memberInfo['member_touxiang'];
        $indate['userid'] = $memberInfo['idmember'];
        $indate['user_realname'] = $memberInfo['member_realname'];
        $indate['qudaoid'] = 0;

        //为用户添加券
        $this->rankQuanModel->insert($indate);
        //将兑换订单更改为已同步
        $this->integralOrderModel->where([
            'id' => $integralGoodsData['id']
        ])->update([
            'sync_status' => 1,
            'coupon_sn' => $quan_number
        ]);
    }

    /**
     * 同步到馆优惠券
     * @param $integralGoodsData
     * @param $memberInfo
     * @throws Exception
     * @throws \think\db\exception\DbException
     * @throws \think\exception\PDOException
     * @author chailiwei
     */
    public function syncOfflineCoupon($integralGoodsData, $memberInfo)
    {
        $quanModel = new QuanDingdanModel();
        $couponRuleModel = new CouponRuleModel();
        $memberHasQuanModel = new MemberHasQuan();
        $quanLogModel = new QuanLogModel();
        $caozuoLogModel = new CaozuoLogModel();

        $offlineType = $integralGoodsData['integral_goods_offline']['offline_type'];

        $ruleData = [];
        if ($offlineType == 1) {
            //满减优惠券
            $quan_num = 'JFMJQ' . date('d') . strtoupper($this->getRandChar(8));
        } elseif ($offlineType == 2) {
            //课程券
            $quan_num = 'JFKCQ' . date('d') . strtoupper($this->getRandChar(8));
        } elseif ($offlineType == 3) {
            //精品课试听券
            $quan_num = 'JFSTQ' . date('d') . strtoupper($this->getRandChar(8));
        } elseif ($offlineType == 4) {
            //代金券
            $quan_num = 'JFDJQ' . date('d') . strtoupper($this->getRandChar(8));
        }
        foreach ($integralGoodsData['integral_goods_offline']['coupon_rule'] as $key => $value) {
            $ruleData[] = [
                'quan_number' => $quan_num,
                'shopid' => $value['shopid'],
                'use_week' => $value['use_week'] ?? '',
                'series_id' => $value['series_id'] ?? 0,
                'type_start' => $value['type_start'] ?? '',
                'type_end' => $value['type_end'] ?? '',
            ];
        }
        $validDays = $integralGoodsData['integral_goods_offline']['valid_days'];

        //同步用户优惠券信息---订单次数券
        if ($offlineType == 2) {
            $quan_arr = [
                'quan_number' => $quan_num,
                'quan_type' => 1,
                'quan_status' => 3,
                'lingqu_time' => date('Y-m-d H:i:s'),
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime("+ $validDays day")),
                'create_time' => date('Y-m-d H:i:s'),
                'quan_cishu' => 1,
                'shopid' => implode(array_column($ruleData, 'shopid'), ','),
            ];
        } elseif ($offlineType == 1) {
            //满减券
            $quan_arr = [
                'quan_number' => $quan_num,
                'quan_type' => 2,
                'quan_status' => 3,
                'lingqu_time' => date('Y-m-d H:i:s'),
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime("+ $validDays day")),
                'create_time' => date('Y-m-d H:i:s'),
                'lesson' => $integralGoodsData['integral_goods_offline']['lesson'],
                'man_lesson' => $integralGoodsData['integral_goods_offline']['man_lesson'],
                'shopid' => implode(array_column($ruleData, 'shopid'), ','),
            ];
        } elseif ($offlineType == 3) {
            //精品课试听券
            $quan_arr = [
                'quan_number' => $quan_num,
                'quan_type' => 3,
                'quan_status' => 3,
                'lingqu_time' => date('Y-m-d H:i:s'),
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime("+ $validDays day")),
                'create_time' => date('Y-m-d H:i:s'),
                'shopid' => implode(array_column($ruleData, 'shopid'), ','),
            ];
        } elseif ($offlineType == 4) {
            //代金券
            $quan_arr = [
                'quan_number' => $quan_num,
                'quan_type' => 4,
                'quan_status' => 3,
                'lingqu_time' => date('Y-m-d H:i:s'),
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime("+ $validDays day")),
                'create_time' => date('Y-m-d H:i:s'),
                'price' => $integralGoodsData['integral_goods_offline']['price'],
                'shopid' => implode(array_column($ruleData, 'shopid'), ','),
            ];
        }

        $member_has_quan_arr = [
            'memberid' => $memberInfo['idmember'],
            'member_phone' => $memberInfo['member_phone'],
            'unionid' => $memberInfo['unionid'],
            'quan_number' => $quan_num
        ];
        $quanModel->insert($quan_arr);
        if (!empty($ruleData)) {
            $couponRuleModel->insertAll($ruleData);
        }
        $memberHasQuanModel->insert($member_has_quan_arr);
        $quanLogModel->insert([
            'log_type' => 'lingquDingdanQuan',
            'log_title' => '积分兑换优惠券',
            'log_content' => $memberInfo['member_nickname'] . '于' . date('Y-m-d H:i:s') . '从积分商城中兑换优惠券券,券码:' . $quan_num,
            'caozuorenid' => $memberInfo['idmember'],
            'caozuoren_name' => $memberInfo['member_nickname'],
            'quan_number' => $quan_num,
            'quan_status' => 3,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        $caozuoLogModel->insert([
            'log_type' => 'duihuan',
            'log_title' => '积分兑换优惠券',
            'log_content' => $memberInfo['member_nickname'] . '于' . date('Y-m-d H:i:s') . '从积分商城中兑换优惠券券,券码:' . $quan_num,
            'create_time' => date('Y-m-d H:i:s'),
            'caozuorenid' => $memberInfo['idmember'],
            'subjectionid' => $quan_num
        ]);
        //将兑换订单更改为已同步
        $this->integralOrderModel->where([
            'id' => $integralGoodsData['id']
        ])->update([
            'sync_status' => 1,
            'coupon_sn' => $quan_num
        ]);
    }

    //兑换列表
    public function logIndex($type)
    {

    }


    /**
     * 获取随机字符串
     * @param type $length
     * @return string
     */
    function getRandChar($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

}