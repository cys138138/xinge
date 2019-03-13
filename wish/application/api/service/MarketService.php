<?php

namespace app\api\service;

use Exception;
use think\Db;

class MarketService {
   
    /**
     * 取消买
     */
    public static function cancelBuy($userId, $MarketId) {
        $aWhere = ['uid' => $userId, 'id' => $MarketId, 'type' => 0, 'status' => 0];
        Db::startTrans();
        try {
            //1.更新买卖市场
            Db::name('art_star_market')
                    ->where($aWhere)
                    ->update([
                        'status' => 2,
                        'is_cancel' => 1,
                        'cancel_time' => NOW_TIME,
            ]);
            Db::name('frozen')->where(['other_id' => $MarketId, 'type' => 1, 'status' => 1])->update([
                'status' => 3,
                'settlement_time' => NOW_TIME,
            ]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return true;
        }
    }

    /**
     * 取消卖
     */
    public static function cancelSale($userId, $MarketId) {
        $aWhere = ['uid' => $userId, 'id' => $MarketId, 'type' => 1, 'status' => 0];
        //开启事务处理
        Db::startTrans();
        try {
            //1.更新买卖市场
            Db::name('art_star_market')
                    ->where($aWhere)
                    ->update([
                        'status' => 2,
                        'is_cancel' => 1,
                        'cancel_time' => NOW_TIME,
            ]);
            //2.时间取消解冻
            Db::name('user_time_frozen')->where(['market_id' => $MarketId, 'status' => 1])->update([
                'status' => 3,
                'update_time' => NOW_TIME,
            ]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

}
