<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\lib\WxappConfig;
use app\api\service\OrderService;
use app\api\service\UserService;
use Exception;
use think\Db;
use WeChat\Contracts\Tools;
use WeChat\Pay;

/**
 * 支付异步通知接口
 */
class Notify extends BaseController {

    /**
     * 支付回调
     */
    public function payCallback() {
        $receipt = file_get_contents("php://input", 'r');
        $aInput = Tools::xml2arr($receipt);
        //记录日志信息
        Db::name('app_notice_log')->insert([
            'create_at' => date('Y-m-d H:i:s', NOW_TIME),
            'content' => json_encode($aInput),
        ]);
        if (!isset($aInput['transaction_id'])) {
            return $this->returnMessage();
        }
        $wechat = new Pay(WxappConfig::getConfig());
        //验证单号
        $result = $wechat->queryOrder(['transaction_id' => $aInput['transaction_id']]);
        if (!isset($result['out_trade_no'])) {
            return $this->returnMessage();
        }
        $orderSn = $aInput['out_trade_no'];
        //$money = $aInput['total_fee'] / 100; //订单金额
        $aOrder = Db::name('app_order')->where(['sn' => $orderSn])->find();
        if (!$aOrder) {
            return $this->returnMessage();
        }
        //已经成功通知
        if ($aOrder['status'] == 1) {
            return $this->returnMessage(1);
        }
        //开启事务处理
        Db::startTrans();
        try {
            //1.设置当前订单状态
            OrderService::setSuccessOrder($orderSn);
            $money = $aInput['total_fee'] / 100; //订单金额
            //多份情况
            $wishId = isset($aInput['attach']) ? $aInput['attach'] : 0;
            $aLastWish = Db::name('wish')->where(['id' => $wishId])->find();
            if($aLastWish){
                Db::name('wish_order_log')->insert([
                    'remark' => '',
                    'create_time' => NOW_TIME,
                    'type' => 1,
                    'uid' => $aLastWish['uid'],
                    'money' => $money,
                ]);
                Db::name('wish_order')->insert([
                    'wish_id' => $wishId,
                    'remark' => '私有燃料购买',
                    'create_time' => NOW_TIME,
                    'uid' => $aLastWish['uid'],
                    'money' => $money,
                ]);
            }
            Db::commit();
            return $this->returnMessage(1);
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->returnMessage(0);
        }
    }

    /**
     * 通知接口
     * @param type $type
     */
    public function returnMessage($type = 0) {
        if ($type) {
            exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
        }
        exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
    }

}
