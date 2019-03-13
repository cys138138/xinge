<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\MarketService;
use app\api\service\StarService;
use app\api\service\UserService;
use think\Db;
use think\Exception;

/**
 * 交易市场
 */
class Market extends BaseController {

    /**
     * 获取买卖列表（只会是当天的）
     */
    public function getMarketList() {
        $starId = (int) $this->request->get('star_id', 0);
        //0 买方市场 1 卖方市场
        $type = (int) $this->request->get('type', 0);
        $aMarketList = Db::name('art_star_market')
                        ->where(['m.star_id' => $starId, 'm.status' => 0, 'm.type' => $type])
                        ->whereBetween('m.create_time', static::getTodayTime())
                        ->alias('m')
                        ->leftJoin('art_star st', 'st.id = m.star_id')
                        ->leftJoin('user_open_binds uob', 'uob.id = m.uid')
                        ->field('m.*,uob.open_nickname as user_name,st.name,st.code')
                        ->order('m.price desc,m.time_length desc')->select();
        if (!$aMarketList) {
            $this->error('暂无数据');
        }
		$i = 1;
		$tName = ['买','转'];
		foreach($aMarketList as &$aItem){
			$aItem['user_name'] = $tName[$type] . $i;
			$i++;
		}
        return $this->success('获取成功1', null, $aMarketList);
    }

    //售卖自己买到的时间（发布到市场）注意每天用户售卖的价格都不一样，开盘价定
    public function wantSale() {
        $userId = (int) $this->request->post('uid', 0);
        $starId = (int) $this->request->post('star_id', 0);
        //买多少钱单价
        $price = (double) $this->request->post('price', 0.00);
        //卖多少
        $timeLength = (int) $this->request->post('time_length', 0);
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        if (!$aStar) {
            $this->error('艺人不存在');
        }

        $payPassword = (string) $this->request->post('pay_password', '');
        if (!$payPassword) {
            $this->error('支付密码不能为空');
        }
        if (!UserService::checkPayPassword($userId, $payPassword)) {
            $this->error('支付密码错误');
        }

        //今天开盘价
        $targetPrice = StarService::getPreDayLastOrderOnePrice($starId);
        //如果找不到交易采用第一次入市价格
        if ($targetPrice === false) {
            $targetPrice = $aStar['in_market_price'];
        }
        //获取价格区间
        list($minPrice, $maxPrice) = StarService::getMaxMinPrice($targetPrice);
        if ($price < $minPrice || $price > $maxPrice) {
            $this->error('价格必须在最高价和最低价之间');
        }
        $userStarTime = UserService::getStarRemaindTime($userId, $starId);
        if ($userStarTime < $timeLength) {
            $this->error('时长超过剩余');
        }
        $totalMoney = $timeLength * $price;
        //手续费
        $platefromCommission = StarService::getPlateformCommission($totalMoney);
        $nowTime = time();
        //开启事务处理
        Db::startTrans();
        try {
            //添加到市场表
            $mMarketId = Db::name('art_star_market')->insertGetId([
                'star_id' => $starId,
                'time_length' => $timeLength,
                'create_time' => $nowTime,
                'total_money' => $totalMoney + $platefromCommission,
                'update_time' => $nowTime,
                'type' => 1,
                'uid' => $userId,
                'price' => $price, //一票卖多少钱
                'platform_commission' => $platefromCommission, //卖出成功平台收取手续费
                'total_sale_money' => $totalMoney, //卖出成功给用户金额
            ]);
            //冻结时间
            Db::name('user_time_frozen')->insert([
                'time' => $timeLength, //冻结多久时间
                'star_id' => $starId,
                'create_time' => $nowTime,
                'update_time' => $nowTime,
                'uid' => $userId,
                'market_id' => $mMarketId,
                'status' => 1,
            ]);
            Db::commit();
            $this->success('发布卖出成功');
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('发布卖出失败，请重试。。');
        }
        $this->error('发布卖出失败，请重试。。');
    }

    /**
     * 提交买入需要
     */
    public function wantBuy() {
        $userId = (int) $this->request->post('uid', 0);
        $starId = (int) $this->request->post('star_id', 0);
        //买多少钱单价
        $price = (double) $this->request->post('price', 0.00);
        //卖多少
        $timeLength = (int) $this->request->post('time_length', 0);
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        if (!$aStar) {
            $this->error('艺人不存在');
        }

        $payPassword = (string) $this->request->post('pay_password', '');
        if (!$payPassword) {
            $this->error('支付密码不能为空');
        }
        if (!UserService::checkPayPassword($userId, $payPassword)) {
            $this->error('支付密码错误');
        }

        //今天开盘价
        $targetPrice = StarService::getPreDayLastOrderOnePrice($starId);
        //如果找不到交易采用第一次入市价格
        if ($targetPrice === false) {
            $targetPrice = $aStar['in_market_price'];
        }
        //获取价格区间
        list($minPrice, $maxPrice) = StarService::getMaxMinPrice($targetPrice);
        if ($price < $minPrice || $price > $maxPrice) {
            $this->error('价格必须在最高价和最低价之间');
        }

        $totalMoney = $timeLength * $price;
        //手续费
        $platefromCommission = StarService::getPlateformCommission($totalMoney);
        //余额不足
        if(UserService::getUserRemainder($userId) < ($totalMoney + $platefromCommission)){
            $this->error('可用余额不足，请充值');
        }
        $nowTime = time();
        //开启事务处理
        Db::startTrans();
        try {


            //添加到市场表
            $market_id = Db::name('art_star_market')->insertGetId([
                'star_id' => $starId,
                'time_length' => $timeLength,
                'create_time' => $nowTime,
                'total_money' => $totalMoney + $platefromCommission,
                'update_time' => $nowTime,
                'type' => 0, //需要买入
                'uid' => $userId,
                'price' => $price, //一票卖多少钱
                'platform_commission' => $platefromCommission, //卖出成功平台收取手续费
                'total_sale_money' => $totalMoney, //卖出成功给用户金额
            ]);


            //冻结用户金额
            Db::name('frozen')->insert([
                'money' => $totalMoney + $platefromCommission, //冻结金额
                'star_id' => $starId,
                'create_time' => $nowTime,
                'uid' => $userId,
                'status' => 1,
                'type' => 1, //买入冻结类型
                'other_id' => $market_id, //带上id
            ]);

            Db::commit();
            $this->success('发布买入成功');
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('发布买入失败，请重试。。');
        }
        $this->error('发布买入失败，请重试。。');
    }

    /**
     * 在卖方市场选择一条信息买入
     */
    public function buy() {
        $userId = (int) $this->request->post('uid', 0);
        $MarketId = (int) $this->request->post('market_id', 0);
        $aMarket = Db::name('art_star_market')->where(['id' => $MarketId, 'type' => 1, 'status' => 0])->find();
        if (!$aMarket) {
            $this->error('找不到记录。。');
        }

        $payPassword = (string) $this->request->post('pay_password', '');
        if (!$payPassword) {
            $this->error('支付密码不能为空');
        }
        if (!UserService::checkPayPassword($userId, $payPassword)) {
            $this->error('支付密码错误');
        }

        $nowTime = time();
        $aTodayTime = static::getTodayTime();
        if ($nowTime > $aTodayTime[1]) {
            $this->error('请购买今天之内的交易。。');
        }
        if ($aMarket['uid'] == $userId) {
            $this->error('不能自己购买自己的交易');
        }
        //取出价钱
        $money = UserService::getUserRemainder($userId);
        //余额不足
        if ($money < $aMarket['total_money']) {
            $this->error('余额不足请先充值。。');
        }
        $aStar = Db::name('art_star')->where([
                    'id' => $aMarket['star_id'],
                ])->find();
        //减购买用户余额 加卖用户金额
        Db::startTrans();
        try {
            //1.减少购买用户余额
            UserService::reduceUserMoney($userId, $aMarket['total_money'], 2, "买入-{$aStar['name']}-{$aStar['code']}");
            //2.给卖出用户加余额(注意金额是扣除手续费的金额)
            UserService::addUserMoney($aMarket['uid'], $aMarket['total_sale_money'], 3, "转让-{$aStar['name']}-{$aStar['code']}");
            //3.更新售卖信息
            Db::name('art_star_market')->where(['id' => $MarketId])->update([
                'update_time' => $nowTime,
                'status' => 1, //设置交易完成
                'is_success' => 1,
                'buy_time' => $nowTime,
            ]);
            //4.给用户加上明星时间
            UserService::addStarTime($userId, $aMarket['star_id'], $aMarket['time_length']);
            //5.减少卖出用户的时间
            UserService::reduceStarTime($aMarket['uid'], $aMarket['star_id'], $aMarket['time_length']);
            //6.解冻冻结时间
            Db::name('user_time_frozen')->where(['uid' => $aMarket['uid'], 'market_id' => $MarketId])->update([
                'update_time' => $nowTime,
                'status' => 2, //设置解冻状态
            ]);
            Db::commit();
            $this->success('买入成功');
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('买入失败11，请重试。。' . var_export($e));
        }
    }

    /**
     * 在买方市场选择一条信息卖给他
     */
    public function sale() {
        //用户id
        $userId = (int) $this->request->post('uid', 0);
        //选择记录id
        $MarketId = (int) $this->request->post('market_id', 0);
        $aMarket = Db::name('art_star_market')->where(['id' => $MarketId, 'type' => 0, 'status' => 0])->find();
        if (!$aMarket) {
            $this->error('找不到记录。。');
        }

        $payPassword = (string) $this->request->post('pay_password', '');
        if (!$payPassword) {
            $this->error('支付密码不能为空');
        }
        if (!UserService::checkPayPassword($userId, $payPassword)) {
            $this->error('支付密码错误');
        }

        $nowTime = time();
        $aTodayTime = static::getTodayTime();
        if ($nowTime > $aTodayTime[1]) {
            $this->error('请卖给今天之内的交易。。');
        }
        if ($aMarket['uid'] == $userId) {
            $this->error('不能自己购买自己的交易');
        }

        //1.拿出自己明星时间
        $myStarTime = UserService::getStarRemaindTime($userId, $aMarket['star_id']);
        if ($myStarTime < $aMarket['time_length']) {
            $this->error('拥有时间不足，交易无法进行');
        }

        $aStar = Db::name('art_star')->where([
                    'id' => $aMarket['star_id'],
                ])->find();

        //2.减购买用户余额 加卖用户金额
        Db::startTrans();
        try {
            //1.减少购买用户余额
            UserService::reduceUserMoney($aMarket['uid'], $aMarket['total_money'], 2, "买入-{$aStar['name']}-{$aStar['code']}");
            //2.给卖出用户加余额(注意金额是扣除手续费的金额)
            UserService::addUserMoney($userId, $aMarket['total_sale_money'], 3, "转让-{$aStar['name']}-{$aStar['code']}");
            //3.更新售卖信息
            Db::name('art_star_market')->where(['id' => $MarketId])->update([
                'update_time' => $nowTime,
                'status' => 1, //设置交易完成
                'is_success' => 1,
                'buy_time' => $nowTime,
            ]);
            //4.给用户加上明星时间
            UserService::addStarTime($aMarket['uid'], $aMarket['star_id'], $aMarket['time_length']);
            //5.减少卖出用户的时间
            UserService::reduceStarTime($userId, $aMarket['star_id'], $aMarket['time_length']);

            //6.处理要买用户的押金信息
            Db::name('frozen')->where(['uid' => $aMarket['uid'], 'star_id' => $aMarket['star_id'], 'type' => 1, 'other_id' => $aMarket['id']])->update([
                'settlement_time' => $nowTime,
                'status' => 2, //设置已结算
            ]);

            Db::commit();
            $this->success('成功卖出');
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('卖出失败，请重试。。' . var_export($e));
        }
    }

    public static function getTodayTime() {
        return [
            strtotime(date('Y-m-d 00:00:00')),
            strtotime(date('Y-m-d 00:00:00', strtotime('+1 day'))),
        ];
    }

    /**
     * 获取我的买卖列表
     */
    public function getUserMarketList() {
        $userId = (int) $this->request->get('uid', 0);
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 10);
        $list = Db::name('art_star_market')->field('asm.*,st.name,st.code')->where(['uid' => $userId])->alias('asm')->leftJoin('art_star st', 'st.id = asm.star_id')->order('buy_time desc,update_time desc')->page($page, $pageSize)->select();
        foreach ($list as &$aItem) {
            $aItem['status_name'] = '交易中';
            if ($aItem['status'] == 1) {
                $aItem['status_name'] = '交易完成';
            }
            $aItem['create_date'] = date('Y-m-d', $aItem['create_time']);
            $aItem['create_date_time'] = date('H:i:s', $aItem['create_time']);
        }
        if (!$list) {
            $list = [];
        }
        $this->success('获取成功', null, $list);
    }

    /**
     * 取消买卖
     */
    public function cancel() {
        //用户id
        $userId = (int) $this->request->post('uid', 0);
        //选择记录id
        $MarketId = (int) $this->request->post('market_id', 0);
        $aWhere = ['uid' => $userId, 'id' => $MarketId, 'type' => 0, 'status' => 0];
        $aMarket = Db::name('art_star_market')->where($aWhere)->find();
        if (!$aMarket) {
            $this->error('记录已经取消。。');
        }
        $result = false;
        //2.解冻冻结信息
        if ($aMarket['type'] == 0) {
            $result = MarketService::cancelBuy($userId, $MarketId);
        } else {
            $result = MarketService::cancelSale($userId, $MarketId);
        }
        if (!$result) {
            $this->error('取消失败，请重试。。');
        }
        $this->success('取消成功');
    }

}
