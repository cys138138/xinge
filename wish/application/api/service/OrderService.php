<?php

namespace app\api\service;

use app\lib\Str;
use think\Db;

class OrderService {

    public static $tabelName = 'app_order';

    /**
     * 创建订单号
     * @param array $user
     */
    public static function createOrderSn($userId, $money, $remark = '') {
        $sn = static::getSn();
        $result = Db::name(static::$tabelName)->insertGetId([
                    'sn' => $sn,
                    'create_time' => NOW_TIME,
                    'title' => $remark,
                    'uid' => $userId,
                    'money' => $money,
                ]) ? true : false;
        if ($result) {
            return $sn;
        }
        return false;
    }
    
    /**
     * 设置订单成功
     * @param type $orderSn
     * @return boolean
     */
    public static function setSuccessOrder($orderSn) {
        $aOrder = Db::name(static::$tabelName)->where(['sn' => $orderSn])->find();
        if (!$aOrder) {
            return false;
        }
        return Db::name(static::$tabelName)->where(['sn' => $orderSn])->update([
                    'pay_time' => NOW_TIME,
                    'status' => 1
        ]);
    }

    /**
     * 生成订单号
     * @return type
     */
    public static function getSn() {
        $str = Str::randString(6, 1);
        return date('YmdHis', NOW_TIME) . $str;
    }
    

}
