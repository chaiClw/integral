<?php

namespace integral\model;

use think\Model;

class BaseModel extends Model {

    protected $error = 0;
    protected $table;
    protected $pk;
    protected $rule = [];
    protected $msg = [];
    protected $Validate;

    public function __construct($data = []) {
        parent::__construct($data);
    }

    /**
     * 列表查询
     *
     * @param unknown $page_index
     * @param number $page_size
     * @param string $order
     * @param string $where
     * @param string $field
     */
    public function pageQuery($page_index, $page_size, $condition, $order, $field) {

        $count = $this->where($condition)->count();
        if ($page_size == 0) {
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->select()->toArray();
            $page_count = 1;
        } else {
            $start_row = $page_size * ($page_index - 1);
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->limit($start_row . "," . $page_size)
                ->select()->toArray();
            if ($count % $page_size == 0) {
                $page_count = $count / $page_size;
            } else {
                $page_count = (int) ($count / $page_size) + 1;
            }
        }


        return array(
            'data' => $list,
            'total_count' => $count,
            'page_count' => $page_count
        );

    }


    /**
     * 列表分组查询
     *
     * @param unknown $page_index
     * @param number $page_size
     * @param string $order
     * @param string $where
     * @param string $field
     */
    public function pageQueryGroup($page_index, $page_size, $condition, $order, $field, $group) {

        $count = $this->where($condition)->group($group)->count();
        if ($page_size == 0) {
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->group($group)
                ->select()->toArray();
            $page_count = 1;
        } else {
            $start_row = $page_size * ($page_index - 1);
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->group($group)
                ->limit($start_row . "," . $page_size)
                ->select()->toArray();
            if ($count % $page_size == 0) {
                $page_count = $count / $page_size;
            } else {
                $page_count = (int) ($count / $page_size) + 1;
            }
        }

        return array(
            'data' => $list,
            'total_count' => $count,
            'page_count' => $page_count
        );

    }



    /**
     * 获取一定条件下的列表
     * @param unknown $condition
     * @param unknown $field
     */
    public function getQuerys($condition, $field, $order='',$limit=0) {
        if($limit == 0){
            $list = $this->field($field)->where($condition)->order($order)->select()->toArray();
        }else{
            $list = $this->field($field)->where($condition)->order($order)->limit("0,$limit")->select()->toArray();
        }
        return $list;
    }

    /**
     * 获取单条记录的基本信息
     *
     * @param unknown $condition
     * @param string $field
     */
    public function getInfo($condition = '', $field = '*') {
        $info = $this->where($condition)
            ->field($field)
            ->find();
        return $info;
    }

    /**
     * 查询数据的数量
     * @param unknown $condition
     * @return unknown
     */
    public function getCount($condition) {
        $count = $this->where($condition)
            ->count();
        return $count;
    }

    /**
     * 查询条件数量
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */
    public function getSum($condition, $field) {
        $sum = $this->where($condition)
            ->sum($field);
        if (empty($sum)) {
            return 0;
        } else
            return $sum;
    }

    /**
     * 查询数据最大值
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */

    public function getMax($condition, $field) {
        $max = $this->where($condition)
            ->max($field);
        if (empty($max)) {
            return 0;
        } else
            return $max;
    }

    /**
     * 查询数据最小值
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */
    public function getMin($condition, $field) {
        $min = $this->where($condition)
            ->min($field);
        if (empty($min)) {
            return 0;
        } else
            return $min;
    }

    /**
     * 查询数据均值
     * @param unknown $condition
     * @param unknown $field
     */
    public function getAvg($condition, $field) {
        $avg = $this->where($condition)
            ->avg($field);
        if (empty($avg)) {
            return 0;
        } else
            return $avg;
    }

    /**
     * 查询第一条数据
     * @param unknown $condition
     */
    public function getFirstData($condition, $order) {
        $data = $this->where($condition)->order($order)
            ->limit(1)->select()->toArray();
        if (!empty($data)) {
            return $data[0];
        } else
            return '';
    }

    /**
     * 增加相关数据
     * @param array $where 条件
     * @param mixed $data 增加的数量
     *
     */
    public function addData($where=[],$field,$data=0){
        if(empty($data)){
            return $this->where($where)->setInc($field);
        }else{
            return $this->where($where)->setInc($field,$data);
        }
    }

    /**
     * 减少相关数据
     * @param array $where 条件
     * @param mixed $data 减少的数量
     */
    public function decData($where=[],$field,$data=0){
        if(empty($data)){
            return $this->where($where)->setDec($field);
        }else{
            return $this->where($where)->setDec($field,$data);
        }
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @CreateTime 2020/9/5 16:33:19
     * @author: chailiwei
     */
    public function updateModel($where, $data)
    {
        return $this->where($where)->strict(false)->update($data);
    }

}
