<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\lib\WxappConfig;
use app\api\service\OrderService;
use think\Db;
use WeChat\Pay;

/**
 * 充值相关
 */
class Recharge extends BaseController {
    
    
    /**
     * 获取充值金额列表
     * @return type
     */
    public function getRechargeOptionList() {
        $list = Db::name('app_charge_option')->where(['status' => 1, 'is_deleted' => 0])->order('sort desc,money asc')->select();
        if (!$list) {
            $this->success('暂无数据', null, []);
        }
        return $this->success('获取成功', null, $list);
    }

    /**
     * 获取支付参数
     */
    public function getPaySign() {
        $userId = (int) $this->request->post('uid', 0);
        //选择充值记录表id
        $money = (double) $this->request->post('money', 0.00);
        $aUserInfo = Db::name('user_open_binds')->where(['user_id' => $userId])->find();
        if (!$aUserInfo) {
            return $this->error('找不到用户');
        }
        $minRechargePrice = sysconf('min_recharge_price');
        if ($money < $minRechargePrice) {
            return $this->error("充值金额至少{$minRechargePrice}元以上");
        }
        // 3. 创建接口实例
        $wechat = new Pay(WxappConfig::getConfig());

        // 4. 组装参数，可以参考官方商户文档
        $options = [
            'body' => '充值',
            'out_trade_no' => OrderService::createOrderSn($userId, $money, '金猪活动套餐'),
            'total_fee' => (int) ($money * 100), //金额转成分
            'openid' => $aUserInfo['openid'],
            'trade_type' => 'JSAPI',
            'notify_url' => url('@api/Notify/payCallback', '', true, true),
                //'spbill_create_ip' => '127.0.0.1',
        ];
        //获取预支付码
        $prepay = $wechat->createOrder($options);
        $result = $wechat->createParamsForJsApi($prepay['prepay_id']);
        return $this->success('获取成功', null, $result);
    }

}
