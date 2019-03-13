<?php

namespace app\api\service;

use Exception;
use think\Db;

class PurchaseService {
    /**
     * 处理用户申购流程
     * @param type $starId star表id
     * @param type $userId 用id
     * @param type $precent 本次购买成功的票数
     * @param type $thisBuyNums  本次申购所有票数
     * @param type $oneSuccessMoney 本次申购成功总费用
     * @param type $thisTotalPlatformCommission 本次收取的手续费
     * @param type $thisStarOnePrice 单价
     * @param type $thisPurshaseTotalMoney 本次申购用户压的所有申购总价
     * @return boolean
     */
    public static function sumPurchase($starId, $userId, $precent, $thisBuyNums, $oneSuccessMoney, $thisTotalPlatformCommission, $thisStarOnePrice, $thisPurshaseTotalMoney) {
        $nowTime = time();
        //开启事务处理
        Db::startTrans();
        try {
            //1.申购处理结果表添加记录
            $optId = Db::name('art_purchase_result')->insertGetId([
                'star_id' => $starId,
                'buy_nums' => $thisBuyNums, //记录所有购买的秒数
                'success_nums' => $precent,
                'one_price' => $thisStarOnePrice,
                'total_money' => $thisPurshaseTotalMoney, //申购是的汇总金额
                'success_total_money' => $oneSuccessMoney, //申购是的汇总金额
                'platform_commission' => StarService::getPlateformCommission($oneSuccessMoney), //本次平台收的该用户成功申购的手续费
                'uid' => $userId,
                'create_time' => $nowTime,
            ]);

            //2.更改申购提交表数据状态
            Db::name('art_star_purchase')->where(['uid' => $userId, 'star_id' => $starId])->update([
                'status' => 1,
                'opt_time' => $nowTime,
                'purchase_id' => $optId,
            ]);
            //3.更新冻结处理记录（特别注意这里冻结金额不一定是最终扣除的金额，扣除金额要以实际购买成功金额为准）
            Db::name('frozen')->where(['uid' => $userId, 'star_id' => $starId, 'status' => 1])->update([
                'status' => 2,
                'settlement_time' => $nowTime,
            ]);

            //4.扣除余额
            $payTotalMoney = $oneSuccessMoney + $thisTotalPlatformCommission; //申购成功的总价加上所有手续费
            //获取用户
            $userMoney = UserService::getUserMoney($userId);
            
            $aStar = Db::name('art_star')->where([
                        'id' => $starId,
                    ])->find();
            Db::name('user_money_log')->insert([
                'uid' => $userId,
                'money' => $payTotalMoney,
                'create_time' => $nowTime,
                'left' => $userMoney, //上次剩余
                'type' => 2,
                'money_type' => 1, //申购
                'remark' => "申购-{$aStar['name']}-{$aStar['code']}", //申购备注
                'date' => date('Ym'),
            ]);

            //5.给用户表减少余额            
            $aUser = Db::name('users')->where(['id' => $userId])->find();
            Db::name('users')->where(['id' => $userId])->update([
                'money' => $aUser['money'] - $payTotalMoney,
                'updatetime' => $nowTime,
            ]);

            //6.给用户添加明星时间                    
            $aUserTime = Db::name('user_star_time')->where([
                        'uid' => $userId,
                        'star_id' => $starId,
                    ])->find();
            //存在就加时间
            if ($aUserTime) {
                Db::name('user_star_time')->where([
                    'uid' => $userId,
                    'star_id' => $starId,
                ])->update([
                    'update_time' => $nowTime,
                    'time' => $aUserTime['time'] + $precent, //再次申购成功时间相加
                ]);
                //记录日志
                Db::name('user_star_time_log')->insert([
                    'star_id' => $starId,
                    'time' => $aUserTime['time'] + $precent,
                    'uid' => $userId,
                    'type' => 1,
                    'left' => $aUserTime['time'],//上一次剩余
                    'create_time' => $nowTime,
                ]);
            } else {
                Db::name('user_star_time')->insert([
                    'star_id' => $starId,
                    'create_time' => $nowTime,
                    'update_time' => $nowTime,
                    'uid' => $userId,
                    'time' => $precent, //再次申购成功时间相加
                ]);
                //记录日志
                Db::name('user_star_time_log')->insert([
                    'star_id' => $starId,
                    'time' => $precent,
                    'left' => 0,
                    'type' => 1,
                    'uid' => $userId,
                    'create_time' => $nowTime,
                ]);
            }

            // 提交事务
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
     * 取消申购
     * @param type $userId
     * @param type $starId
     */
    public static function cancelPurchase($userId, $starId) {
        $nowTime = time();
        Db::startTrans();
        try {
            //解除押金
            UserService::cancelPurchaseFrozen($userId, $starId);
            //更新申购信息
            Db::name('art_star_purchase')->where(['uid' => $userId, 'star_id' => $starId])->update([
                'status' => 2,
                'opt_time' => $nowTime,
                'cancel_time' => $nowTime,
            ]);
            // 提交事务
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

}
