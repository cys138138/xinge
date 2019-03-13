<?php

namespace app\api\service;

use think\Db;

class StarService {

    /**
     * 获取上一天最后一笔交易单价作为今天的开盘价
     * @param type $starId
     */
    public static function getPreDayLastOrderOnePrice($starId) {
        $aWhere = ['star_id' => $starId,'is_success'=>1];
        //获取最后一笔单价
        $preDayLastData = Db::name('art_star_market')
                ->where($aWhere)
                ->whereBetween('buy_time', static::getPreTime())
                ->order('buy_time desc')
                ->find();
        //如果找不到昨天交易
        if (!$preDayLastData) {
            return static::getLastOrderOnePrice($starId);
        }
        return $preDayLastData['price'];
    }
    /**
     * 获取上一天最后一笔交易单价作为今天的开盘价
     * @param type $starId
     */
    public static function getLastOrderOnePrice($starId) {
        $aWhere = ['star_id' => $starId,'is_success'=>1];
        //今天开始
        $preDayEnd = strtotime(date('Y-m-d 00:00:00'));
        //获取最后一笔单价
        $preDayLastData = Db::name('art_star_market')
                ->where($aWhere)
                ->where('buy_time <'.$preDayEnd)
                ->order('buy_time desc')
                ->find();
        //如果找不到昨天交易
        if (!$preDayLastData) {
            return false;
        }
        return $preDayLastData['price'];
    }

    /**
     * 获取开盘价
     * @param type $starId
     * @return type
     */
    public static function getStarPrice($starId) {
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        //今天开盘价
        $targetPrice = static::getPreDayLastOrderOnePrice($starId);
        //如果找不到交易采用第一次入市价格
        if ($targetPrice === false) {
           return $aStar['in_market_price'];           
        }
        return $targetPrice;
    }

    /**
     * 获取涨跌
     * @param type $starId
     * @return type
     */
    public static function getZD($starId) {
        //获取最近一笔交易
        $current = static::getTodayLastOrderOnePrice($starId);
        //今天开盘价
        $price = static::getStarPrice($starId);
        $result = '0.00';
        //如果有最近交易
        if ($current !== false) {
            //涨
            if ($current > $price) {
                $result = price(($current - $price) / $price) * 100;
            } else if ($current == $price) {
                $result = '0.00';
            } else {
                //跌
                $result = price(($current - $price) / $price) * 100;
            }
        }else{
			//今天没有交易
		}
        return $result;
    }

    /**
     * 获取今天最近一笔交易
     * @param type $starId
     */
    public static function getTodayLastOrderOnePrice($starId) {
        $aWhere = ['star_id' => $starId,'is_success'=>1];
        //获取最后一笔单价
        $preDayLastData = Db::name('art_star_market')
                ->where($aWhere)
                ->where('buy_time >' . strtotime(date('Y-m-d 00:00:00')))
                ->order('buy_time desc')
                ->find();
        //如果找不到昨天交易
        if (!$preDayLastData) {
            return false;
        }
        return $preDayLastData['price'];
    }

    /**
     * 获取上一天时间
     * @return type
     */
    public static function getPreTime() {
        $preDay = strtotime('-1 day');
        //昨天开始
        $preDayStart = strtotime(date('Y-m-d 00:00:00', $preDay));
        //今天开始
        $preDayEnd = strtotime(date('Y-m-d 00:00:00'));
        return [$preDayStart, $preDayEnd];
    }

    public static function getMaxMinPrice($price) {
        $precent = $price * 0.1;
        $minPrice = $price - $precent;
        $maxPrice = $price + $precent;
        return [price($minPrice), price($maxPrice)];
    }

    /**
     * 根据用户申购金额计算所得票数
     * @param type $totalMoney 本次申购收到的总金额
     * @param type $starTotalMoney 本支票数价值金额
     * @param type $userMoney 用户所在本支锁头的金额
     */
    public static function getPurchaseByMoney($totalMoney, $starTotalMoney, $userMoney) {
        return $userMoney * ($starTotalMoney / $totalMoney);
    }

    public static function getPlateformCommission($totalMoney) {
        //千分之1.5
        $shouxuPrecent = 0.0015;
        //手续费 百分 0.15
        $shouxfei = $totalMoney * $shouxuPrecent;
        if ($shouxfei < 0.01) {
            $shouxfei = 0.01;
        }
        return price($shouxfei);
    }
    
    /**
     * 获取某个艺人总共售卖时长
     * @param type $startId
     */
    public static function getSalesNums($startId){
       return Db::name('art_star_purchase')->where(['star_id'=>$startId])->sum('buy_nums');
    }

}
