<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use app\api\service\UserService;
use Exception;
use think\Db;

/**
 * 申购api
 */
class Purchase extends BaseController {

    /**
     * 申购
     */
    public function buy() {
        //哪个用户购买
        $userId = (int) $this->request->get('uid', '');
        //购买秒数数量
        $nums = (int) $this->request->get('nums', '');
        //哪个明星艺人
        $starId = (int) $this->request->get('star_id', '');
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        if (!$aUser) {
            $this->error('用户不存在');
        }
        if ($nums < 1) {
            $this->error('买入数量有问题');
        }
        //检测支付密码
        $payPassword = (string) $this->request->get('pay_password', '');
        if(!$payPassword){
            $this->error('支付密码不能为空');
        }
        if (!UserService::checkPayPassword($userId, $payPassword)) {
            $this->error('支付密码错误');
        }
        
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        if (!$aStar) {
            $this->error('艺人不存在');
        }
        //获取买入价格
        $price = $aStar['purchase_price'];
        $time = time();
        if ($time < $aStar['purchase_start_time']) {
            $this->error('申购未开始');
        }
        if ($time > $aStar['purchase_end_time']) {
            $this->error('申购已结束');
        }
        //总费用
        $totalMoney = $price * $nums;
        //获取手续费
        $shouxfei = StarService::getPlateformCommission($totalMoney);
        //计算用户余额
        $remainder = UserService::getUserRemainder($userId);
        $thisTotalMoney = $totalMoney + $shouxfei;
        if ($thisTotalMoney > $remainder) {
            $this->error('余额不足，请充值后再试。。');
        }
        //计算申购数量是否达到最大
        $buyNumsed = Db::name('art_star_purchase')->where(['uid' => $userId, 'status' => 0])->sum('buy_nums');
        if (($buyNumsed + $nums) > $aStar['time_length']) {
            $this->error('申购达到最大值，本次最多还能申购(' . ($aStar['time_length'] - $buyNumsed) . ')');
        }
        Db::startTrans();
        try {
            //扣押押金
            UserService::frozen($userId, $starId, $thisTotalMoney);
            //记录申购记录
            Db::name('art_star_purchase')->insert([
                'star_id' => $starId,
                'uid' => $userId,
                'status' => 0,
                'buy_nums' => $nums,
                'create_time' => time(),
                'one_price' => $price,
                'money' => $thisTotalMoney,
                'real_money' => $thisTotalMoney - $shouxfei,
                'platform_commission' => $shouxfei,
            ]);
            // 提交事务
            Db::commit();
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('余额不足，请充值后再试。。');
        }
        $this->success('申购成功');
    }

    /**
     * 获取用户还能申购多少数量
     */
    public function getUserRemainPurchaseNums() {
        // 解码数据
        $userId = (int) $this->request->get('uid', 0);
        $starId = (int) $this->request->get('star_id', 0);
        if (!$userId || !$starId) {
            $this->error('缺少参数。。');
        }
        $aStar = Db::name('art_star')->where(['id' => $starId, 'status' => 1])->find();
        if (!$aStar) {
            $this->error('star_id 不存在。。');
        }
        //统计已经申购的数量
        $buyNums = Db::name('art_star_purchase')->where(['uid' => $userId, 'star_id' => $starId, 'status' => 0])->sum('buy_nums');
        $this->success('用户ok', null, ['buy_nums' => $buyNums, 'can_buy_nums' => $aStar['time_length'] - $buyNums, 'total_nums' => $aStar['time_length']]);
    }

    /**
     * 获取我的申购信息
     */
    public function getPurchaseList() {
        $userId = (int) $this->request->get('uid', 0);
        $list = Db::name('art_star_purchase')->where(['uid' => $userId])->select();
        $aDataList = [];
        //合并数据
        foreach ($list as $aItem) {
            //提取出同一个明星的数据
            $aDataList[$aItem['star_id']][] = $aItem;
        }
        $aNesData = [];

        foreach ($aDataList as $k => $aStarList) {
            $aStar = Db::name('art_star')->where(['id' => $k])->find();
            //总数
            $buyNums = 0;
            $oncePrice = 0.00;
            $status = '申购中';
            $successNums = 0;
            if ($aStar['type'] == 2) {
                $aResult = Db::name('art_purchase_result')->where(['star_id' => $k, 'uid' => $userId])->find();
                $successNums = $aResult['success_nums'];
            }
            foreach ($aStarList as $aVal) {
                $buyNums += $aVal['buy_nums'];
                $oncePrice = $aVal['one_price'];
                if ($aVal['status'] == 1) {
                    $status = '申购完成';
                }
            }
            $aNesData[] = [
                'star_id' => $k,
                'name' => $aStar['name'],
                'buyNums' => $buyNums,
                'once_price' => $oncePrice,
                'success_nums' => $successNums,
            ];
        }
        $this->success('获取成功', null, $aNesData);
    }

    /**
     * 获取我的申购明细信息
     */
    public function getPurchaseDetailList() {
        $userId = (int) $this->request->get('uid', 0);
        //哪个明星
        $starId = (int) $this->request->get('star_id', 0);
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        if (!$starId) {
            $this->error('star_id 不存在。。');
        }
        //系统处理情况
        $aResult = Db::name('art_purchase_result')->where(['star_id' => $starId, 'uid' => $userId])->find();
        $total_purchase_nums = 0;
        $succssNums = 0;
        if ($aResult) {
            $succssNums = $aResult['success_nums'];
        }
        $list = Db::name('art_star_purchase')->where(['uid' => $userId, 'star_id' => $starId])->select();
        //合并数据
        foreach ($list as &$aItem) {
            $total_purchase_nums += $aItem['buy_nums'];
            $aItem['status_name'] = '申购中';
            $aItem['star_name'] = $aStar['name'];
            $aItem['code'] = $aStar['code'];
            if ($aItem['status'] == 1) {
                $aItem['status_name'] = '申购完成';
            }
        }

        if (!$list) {
            $list = [];
        }

        $this->success('获取成功', null, ['status_type' => $aStar['type'], 'star_name' => $aStar['name'], 'code' => $aStar['code'], 'data_list' => $list, 'total_purchase_nums' => $total_purchase_nums, 'success_nums' => $succssNums]);
    }

}
