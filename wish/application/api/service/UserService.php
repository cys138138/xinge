<?php

namespace app\api\service;

use Exception;
use think\Db;

class UserService {

    
    public static function getWishTotalMoney($id) {
        return Db::name('wish_order')->where(['wish_id' => $id])->sum('money');
    }
    public static function getWishTotalDay($wishId) {
        $todayS = 60 * 60 * 24;
        $aLastWish = Db::name('wish')->where(['id' => $wishId, 'status' => 1])->find();
        $diffDay = intval((NOW_TIME - $aLastWish['create_time']) / $todayS);
        return $diffDay;
    }
    public static function getWishNumber($wishId) {
        $todayS = 60 * 60 * 24;
        $aLastWish = Db::name('wish')->where(['id' => $wishId, 'status' => 1])->find();        
        return $aLastWish['number'];
    }
    public static function getWishQifuNums($wishId) {
        return Db::name('wish_blessing')->where(['wish_id' => $wishId])->count();
        $fudaiNums = Db::name('wish_blessing')->where(['wish_id' => $wishId])->sum('fudai_shus');
    }

    public static function getWishFudaiNums($wishId) {
        return Db::name('wish_blessing')->where(['wish_id' => $wishId])->sum('fudai_shus');
    }

    public static function getWishNeedMoneyInfo($id) {
        $todayS = 60 * 60 * 24;
        $aWish = Db::name('wish')->where(['id' => $id])->find();
        $okMoney = static::getWishTotalMoney($aWish['id']);
        //1 按天 2 按周 3 按月
        $aData = [];
        //存在时长
        $aData['total_day_nums'] = intval((NOW_TIME - $aWish['create_time']) / $todayS);
        //已经存了多少桶（单位元）
        $aData['total_nums'] = intval($okMoney);
        $aData['today_need_money'] = 0;
        $needMoney = $aWish['target_money'] - $okMoney;
        //距离返回
        $aData['back_total_nums'] = 0;
        if ($aWish['target_type'] == 1) {
            $aWishOrder = Db::name('wish_order')->where(['wish_id' => $id])->whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00')), strtotime(date("Y-m-d 00:00:00", strtotime('+1 day')))])->find();
            if ($aWishOrder) {
                $aData['today_need_money'] = 0;
            } else {
                $aData['today_need_money'] = intval($aWish['one_money']);
            }
            $aData['back_total_nums'] = intval($needMoney / $aWish['one_money']);
        }
        //按周
        else if ($aWish['target_type'] == 2) {
            //计算每7天
            //取出最后一笔跟现在比对是否超过7天如果超过提示
            $aLastWishOrder = Db::name('wish_order')->where(['wish_id' => $id])->order('id desc')->find();
            $diffDay = intval((NOW_TIME - $aLastWishOrder['create_time']) / $todayS);
            if ($diffDay < 7) {
                $aData['today_need_money'] = 0;
            } else {
                $aData['today_need_money'] = intval($aWish['one_money']);
            }
            $aData['back_total_nums'] = intval($needMoney / $aWish['one_money']) * 7;
        }

        //按月份
        else if ($aWish['target_type'] == 3) {
            //计算每7天
            //取出最后一笔跟现在比对是否超过7天如果超过提示
            $aLastWishOrder = Db::name('wish_order')->where(['wish_id' => $id])->order('id desc')->find();
            $diffDay = intval((NOW_TIME - $aLastWishOrder['create_time']) / $todayS);
            if ($diffDay < 30) {
                $aData['today_need_money'] = 0;
            } else {
                $aData['today_need_money'] = intval($aWish['one_money']);
            }
            $aData['back_total_nums'] = intval($needMoney / $aWish['one_money']) * 30;
        }
        return $aData;
    }

    /**
     * 处理提现流程
     */
    public static function withdrawal($userId, $money, $payName, $payAccount) {
        $nowTime = time();
        $remaind = static::getUserRemainder($userId);
        //余额不足
        if ($money > $remaind) {
            return false;
        }
        //开启事务处理
        Db::startTrans();
        try {
            $aData = [
                'star_id' => 0,
                'money' => $money,
                'uid' => $userId,
                'status' => 1,
                'create_time' => $nowTime,
                'type' => 2, //提现
            ];
            $frozen_id = Db::name('frozen')->insertGetId($aData);
            //插入提现表提现
            Db::name('app_withdrawal')->insert([
                'money' => $money,
                'create_time' => $nowTime,
                'uid' => $userId,
                'pay_account' => $payAccount,
                'pay_time' => $nowTime,
                'pay_name' => $payName,
                'date' => date('Ym'),
                'frozen_id' => $frozen_id,
            ]);
            // 提交事务
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    
    /**
     * 检测支付密码是否正确
     * @param type $userId
     * @param type $payPassword
     * @return boolean
     */
    public static function checkPayPassword($userId, $payPassword) {
        $aUserInfo = Db::name('user_infos')->where(['user_id' => $userId])->find();
        if (!$aUserInfo) {
            return false;
        }
        return md5($payPassword) == $aUserInfo['pay_password'] ? true : false;
    }

    
    public static function getQrShareKey($userId){
        return 'user_share_qr_'.$userId;
    }
}
