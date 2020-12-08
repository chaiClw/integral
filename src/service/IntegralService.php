<?php


namespace integral\service;
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
     * @type  用户类型  1在线借阅  2到馆
     */
    public  function  operationIntegral($id=0 , $userid=0 , $type=1){
        if($id<=0 || $userid<=0){
            return ['code'=>0,'msg'=>'数据不完整'];
        }
        $where= [];
        if($type==2){
            $where[]=['entity_id','=',$userid];
        }else{
            $where[]=['online_id','=',$userid];
        }
        $IntegralSetModel = new IntegralSetModel();
        $MemberIntegralModel = new MemberIntegralModel();
        $MemberIntegralLogModel = new MemberIntegralLogModel();
        $set=$IntegralSetModel->where(['id'=>$id])->find();
        if(empty($set))   return ['code'=>0,'msg'=>'奖励规则查询错误'];
        $integral=$MemberIntegralModel->where($where)->value('integral');
        Db::startTrans();
        try {
            $MemberIntegralModel->where($where)->setInc('integral',$set['integral']);
            $MemberIntegralModel->where($where)->setInc('integral_all',$set['integral']);
            $memberIntegral=$MemberIntegralModel->where($where)->find();
            $MemberIntegralLogModel->insert([
                'integral_id'=>$memberIntegral['id'],
                'integral_title'=>$set['title'],
                'integral_number'=>$set['integral'],
                'original_integral'=>$integral,
                'source_id'=>$set['ids'],
                'residue_integral'=>$memberIntegral['integral'],
                'create_time'=>date('Y-m-d H:i:s')
            ]);
            Db::commit();
            return ['code'=>200,'msg'=>'成功'];
        } catch (Exception $exception) {
            Db::rollback();
            return ['code'=>0,'msg'=>'失败'];
        }
    }
}