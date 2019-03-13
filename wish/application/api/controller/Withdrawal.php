<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\UserService;
use think\Db;

/**
 * 提现
 */
class Withdrawal extends BaseController {
    
    
    /**
     * 提交提现信息
     * @return type
     */
    public function addWithdrawal() {
        $userId = (int) $this->request->post('uid', 0);
        $money = (double) $this->request->post('money', 0.00);
        $payName = $this->request->post('pay_name', '');
        $payAccount = $this->request->post('pay_account', '');
        
        $minWithdrawalPrice = sysconf('min_withdrawal_price');
        if ($money < $minWithdrawalPrice) {
            return $this->error("提现金额至少{$minWithdrawalPrice}元以上");
        }
        //获取可用余额
        $canUseMoney = UserService::getUserRemainder($userId);
        if ($canUseMoney < $money) {
            return $this->error('账户可用余额不足提现余额');
        }
        //设置提现信息
        $result = UserService::withdrawal($userId, $money, $payName, $payAccount);
        if (!$result) {
            $this->error('提现失败');
        }
        return $this->success('提现成功');
    }

}
