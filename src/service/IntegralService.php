<?php


namespace integral\service;

use integral\model\DataMemberModel;
use integral\model\IntegralSetModel;
use integral\model\MemberIntegralLogModel;
use integral\model\MemberIntegralModel;
use think\Db;

class IntegralService
{

    /**
     * 积分
     * @return array
     * @id  积分动作ID
     * @userid  用户ID
     * @num  倍数
     * @type  用户类型  1在线借阅  2到馆
     * @multiple  积分翻倍（几倍） 默认1倍
     */
    public function operationIntegral($id = 0, $userid = 0, $type = 1, $num = 0, $multiple = 1)
    {
        if ($id <= 0 || $userid <= 0) {
            return ['code' => 0, 'msg' => '数据不完整'];
        }
        $where = [];
        if ($type == 2) {
            $where[] = ['entity_id', '=', $userid];
        } else {
            $where[] = ['online_id', '=', $userid];
        }
        $IntegralSetModel = new IntegralSetModel();
        $MemberIntegralModel = new MemberIntegralModel();
        $MemberIntegralLogModel = new MemberIntegralLogModel();
        $set = $IntegralSetModel->where(['id' => $id, 'status' => 0])->find();
        if (empty($set)) return ['code' => 0, 'msg' => '规则不存在或被禁用'];
        $integral = $MemberIntegralModel->where($where)->value('integral');
        //倍数换算积分
        if ($set['way_type'] == 1) {
            $set['integral'] = $num * $set['integral'];
        }
        $set['integral'] = $set['integral'] * $multiple;
        Db::startTrans();
        try {
            $memberIntegral = $MemberIntegralModel->where($where)->find();
            if (empty($memberIntegral) && $type == 1) {
                $insertData = $this->syncIntegralOrder($userid);  //同步积分用户信息
                MemberIntegralModel::create($insertData);
                $memberIntegral = $MemberIntegralModel->where($where)->find();
            }
            $MemberIntegralModel->where($where)->setInc('integral', $set['integral']);
            $MemberIntegralModel->where($where)->setInc('integral_all', $set['integral']);
            $MemberIntegralLogModel->insert([
                'integral_id' => $memberIntegral['id'],
                'integral_title' => $set['name'],
                'integral_number' => $set['integral'],
                'original_integral' => $integral,
                'source_id' => $set['id'],
                'residue_integral' => $memberIntegral['integral'],
                'create_time' => date('Y-m-d H:i:s')
            ]);
            Db::commit();
            return ['code' => 200, 'msg' => '成功', 'data' => $set['integral']];
        } catch (\Exception $exception) {
            Db::rollback();
            return ['code' => 0, 'msg' => '失败'];
        }
    }

    /**
     * @param $idmember
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chailiwei
     */
    public function syncIntegralOrder($idmember)
    {
        $offlineId = DataMemberModel::where(['jieyue_memberid' => $idmember])->value('yueke_memberid');

        if (empty($offlineId)) {  //如果到馆是0，证明是纯新用户


            return [
                'online_id' => $idmember,
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time())
            ];
        } else {
            $memberIntegral = MemberIntegralModel::where(['entity_id' => $offlineId])->find();
            if (empty($memberIntegral)) {
                return [
                    'online_id' => $idmember,
                    'entity_id' => $offlineId,
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time())
                ];
            }
        }
    }
}