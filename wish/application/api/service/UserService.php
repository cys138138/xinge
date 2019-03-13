<?php

namespace app\api\service;

use Exception;
use think\Db;

class UserService {

    /**
     * 获取用户余额减去冻结的
     * @param array $user
     */
    public static function getUserRemainder($userId) {
        $userTotalMoney = Db::name('users')->where(['id' => $userId])->sum('money');
        $userTotalFrozenMoeny = static::getUserForzenTotalMoney($userId);
        if ($userTotalMoney == 0) {
            return 0.00;
        }
        return $userTotalMoney - $userTotalFrozenMoeny;
    }

    /**
     * 获取所有冻结金额
     */
    public static function getUserForzenTotalMoney($userId) {
        return Db::name('frozen')->where(['uid' => $userId, 'status' => 1])->sum('money');
    }

    /**
     * 获取用户某个明星拥有时间
     * @param type $userId
     * @param type $starId
     * @return type
     */
    public static function getStarRemaindTime($userId, $starId) {
        $userStaryTotalTime = Db::name('user_star_time')->where(['uid' => $userId, 'star_id' => $starId])->sum('time');
        $userTotalFrozenTime = Db::name('user_time_frozen')->where(['uid' => $userId, 'status' => 1, 'star_id' => $starId])->sum('time');
        return $userStaryTotalTime - $userTotalFrozenTime;
    }

    /**
     * 获取用户余额
     * @param array $user
     */
    public static function getUserMoney($userId) {
        return Db::name('users')->where(['id' => $userId])->sum('money');
    }

    /**
     * 冻结用户押金
     * @param array $user
     */
    public static function frozen($userId, $starId, $money) {
        $userTotalMoney = Db::name('users')->where(['id' => $userId])->sum('money');
        $userTotalFrozenMoeny = Db::name('frozen')->where(['uid' => $userId, 'status' => 1])->sum('money');
        if ($userTotalMoney == 0) {
            return false;
        }
        $remaind = $userTotalMoney - $userTotalFrozenMoeny;
        //余额不足
        if ($money > $remaind) {
            return false;
        }
        $aData = [
            'star_id' => $starId,
            'money' => $money,
            'uid' => $userId,
            'status' => 1,
            'create_time' => time(),
        ];
        return Db::name('frozen')->insertGetId($aData) ? true : false;
    }

    /**
     * 取消申购押金
     * @param type $userId 用户id
     * @param type $starId starId
     * @return type
     */
    public static function cancelPurchaseFrozen($userId, $starId) {
        $aData = [
            'status' => 3,
            'settlement_time' => time(),
        ];
        return Db::name('frozen')->where(['uid' => $userId, 'type' => 0, 'star_id' => $starId])->update($aData) ? true : false;
    }

    /**
     * 减少某给用户明星时间
     * @param type $userId
     * @param type $starId
     * @param type $reduceTime
     * @return boolean
     */
    public static function reduceStarTime($userId, $starId, $reduceTime) {
        $aUserStarTime = Db::name('user_star_time')->field('time')->where(['uid' => $userId, 'star_id' => $starId])->find();
        $time = time();
		if(!$aUserStarTime['time']){
			$aUserStarTime['time'] = 0;
		}
        //开启事务处理
        Db::startTrans();
        try {
            //1.减少时间
            Db::name('user_star_time_log')->insert([
                'star_id' => $starId,
                'time' => $reduceTime,
                'left' => $aUserStarTime['time'],
                'type' => 2,
                'create_time' => $time,
                'uid' => $userId,
            ]);
            $remaindTime = $aUserStarTime['time'] - $reduceTime;
            Db::name('user_star_time')->where(['uid' => $userId, 'star_id' => $starId])->update([
                'update_time' => $time,
                'uid' => $userId,
                'time' => $remaindTime,
            ]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return false;
    }

    /**
     * 增加某给用户明星时间
     * @param type $userId
     * @param type $starId
     * @param type $addTime
     * @return boolean
     */
    public static function addStarTime($userId, $starId, $addTime) {
        $aUserStarTime = Db::name('user_star_time')->field('time')->where(['uid' => $userId, 'star_id' => $starId])->find();
        $oldTime = 0;
        $time = time();

        //不存在则添加
        if (!$aUserStarTime) {
            Db::name('user_star_time')->insert([
                'star_id' => $starId,
                'time' => $addTime,
                'create_time' => $time,
                'update_time' => $time,
                'uid' => $userId,
            ]);
        } else {
            if ($aUserStarTime['time']) {
                $oldTime = $aUserStarTime['time'];
            }
            $remaindTime = $oldTime + $addTime;
            Db::name('user_star_time')->where(['uid' => $userId, 'star_id' => $starId])->update([
                'update_time' => $time,
                'uid' => $userId,
                'time' => $remaindTime,
            ]);
        }

        //1.减少时间
        Db::name('user_star_time_log')->insert([
            'star_id' => $starId,
            'time' => $addTime,
            'left' => $oldTime,
            'type' => 1,
            'create_time' => $time,
            'uid' => $userId,
        ]);
        return true;
    }

    /**
     * 扣除用户余额
     * @param type $userId
     * @param type $money
     */
    public static function reduceUserMoney($userId, $money, $moneyType = 1, $remark = '') {
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        $nowTime = time();
        //减少余额
        Db::name('user_money_log')->insert([
            'money' => $money,
            'left' => $aUser['money'],
            'type' => 2,
            'create_time' => $nowTime,
            'uid' => $userId,
            'money_type' => $moneyType,
            'remark' => $remark, //备注
            'date' => date('Ym'),
        ]);
        //记录日志
        Db::name('users')->where(['id' => $userId])->update([
            'money' => $aUser['money'] - $money,
            'updatetime' => $nowTime,
        ]);
    }

    /**
     * 增加用户余额
     * @param type $userId
     * @param type $money
     */
    public static function addUserMoney($userId, $money, $moneyType = 1, $remark = '') {
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        $nowTime = time();
        //减少余额
        Db::name('user_money_log')->insert([
            'money' => $money,
            'left' => $aUser['money'],
            'type' => 1,
            'create_time' => $nowTime,
            'uid' => $userId,
            'money_type' => $moneyType,
            'remark' => $remark, //备注
            'date' => date('Ym'),
        ]);
        //记录日志
        Db::name('users')->where(['id' => $userId])->update([
            'money' => $aUser['money'] + $money,
            'updatetime' => $nowTime,
        ]);
    }

    /**
     * 获取用户某个明星的买入平均成本
     * @param type $userId
     * @param type $starId
     *   计算方案是 所有购买成功的总金额包含给平台手续费 / 数量
     */
    public static function getUserStarIncome($userId, $starId) {
        $aCondition1 = ['uid' => $userId, 'star_id' => $starId];
        $success_total_money = Db::name('art_purchase_result')->where($aCondition1)->sum('success_total_money');
        $platform_commission = Db::name('art_purchase_result')->where($aCondition1)->sum('platform_commission');
        $success_nums = Db::name('art_purchase_result')->where($aCondition1)->sum('success_nums');
        //买入成功的也算
        $aCondition = ['uid' => $userId, 'star_id' => $starId, 'type' => 0, 'status' => 1, 'is_success' => 1];
        //除了手续费费用
        $market_success_total_money = Db::name('art_star_market')->where($aCondition)->sum('total_sale_money');
        //手续费
        $market_platform_commission = Db::name('art_star_market')->where($aCondition)->sum('platform_commission');
        //数量
        $market_success_nums = Db::name('art_star_market')->where($aCondition)->sum('time_length');

        $yongjin = ($success_total_money + $platform_commission + $market_success_total_money + $market_platform_commission) / ($success_nums + $market_success_nums);

        return price($yongjin);
    }

    /**
     * 获取用户某个明星的买入总成本价
     * @param type $userId
     * @param type $starId
     *   计算方案是 所有购买成功的总金额包含给平台手续费 / 数量
     */
    public static function getUserStarCost($userId, $starId) {
        $aCondition1 = ['uid' => $userId, 'star_id' => $starId];
        $success_total_money = Db::name('art_purchase_result')->where($aCondition1)->sum('success_total_money');
        $platform_commission = Db::name('art_purchase_result')->where($aCondition1)->sum('platform_commission');
        //买入成功的也算
        $aCondition = ['uid' => $userId, 'star_id' => $starId, 'type' => 0, 'status' => 1, 'is_success' => 1];
        //除了手续费费用
        $market_success_total_money = Db::name('art_star_market')->where($aCondition)->sum('total_sale_money');
        //手续费
        $market_platform_commission = Db::name('art_star_market')->where($aCondition)->sum('platform_commission');
        return ($success_total_money + $platform_commission + $market_success_total_money + $market_platform_commission);
    }

    /**
     * 获取总市值
     */
    public static function getUserTotalBuyStarMarketCapitalization($userId) {
        $aIdList = Db::name('user_star_time')->where(['uid' => $userId])->select();
        //总成本
        $total_money = 0.00;
        //总收益
        $total_income = 0.00;
        foreach ($aIdList as $item) {
            //成本
            $total_money += UserService::getUserStarCost($userId, $item['star_id']);
            $cost_price = UserService::getUserStarIncome($userId, $item['star_id']);
            //收益 今天的价格 - 成本 * 持有数量
            $earnings_price = (StarService::getStarPrice($item['star_id']) - $cost_price) * $item['time'];
            $total_income += $earnings_price;
        }
        return price($total_money + $total_income);
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
