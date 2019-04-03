<?php

namespace app\admin\controller;

use app\api\lib\WxappConfig;
use app\api\service\OrderService;
use app\api\service\UserService;
use controller\BasicAdmin;
use think\Db;
use think\Exception;
use WeChat\Pay;

/**
 * 控制器
 * @package app\admin\controller
 */
class Withdrawal extends BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'app_withdrawal';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

       
        return parent::_list($this->getSearch());
    }
    
    protected function getSearch($status=''){
         list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["uid"]) && $get["uid"] !== '') && $db->whereLike("ub.username", "%{$get['uid']}%");
        (isset($get["suname"]) && $get["suname"] !== '') && $db->whereLike("su.username", "%{$get["suname"]}%");
        if ($status !== '') {
            $db->where(["app_withdrawal.status" => $status]);
        } else {
            (isset($get["status"]) && $get["status"] !== '') && $db->where(["app_withdrawal.status" => $get["status"]]);
        }

        (isset($get["money"]) && $get["money"] !== '') && $db->where(["app_withdrawal.money" => $get["app_withdrawal.money"]]);
        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('app_withdrawal.create_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['opt_time']) && $get['opt_time'] !== '') {
            list($start, $end) = explode(' - ', $get['opt_time']);
            $db->whereBetween('app_withdrawal.opt_time', [strtotime($start), strtotime($end)]);
        }
        $db->field('app_withdrawal.*,ub.username as open_nickname,su.username as suname');
        $db->leftJoin('users ub', 'ub.id = app_withdrawal.uid');
        $db->leftJoin('system_user su', 'su.id = app_withdrawal.opt_uid');
        $db->order('create_time desc');
        return $db;
    }


    /**
     * 待提现
     */
    public function nowithdrawal() {
        $result = parent::_list($this->getSearch(0), true, false);
        $this->assign('title', '待提现列表');
        return $this->fetch('index', $result);
    }

    /**
     * 格式化输出
     * @param type $param
     */
    protected function _form_filter(&$param) {
        if (!$this->request->isPost()) {
            
        }
    }

    /**
     * 设置提现失败
     */
    public function fail() {
        $ids = explode(',', $this->request->post('id', ''));
        $nowTime = time();
        foreach ($ids as $id) {
            //防止重复提现
            $aWithdrawal = Db::name($this->table)->where(['id' => $id, 'status' => 0])->find();
            if (!$aWithdrawal) {
                continue;
            }
            Db::startTrans();
            try {
                //设置提现状态
                Db::name('app_withdrawal')->where(['id' => $id])->update([
                    'opt_time' => $nowTime,
                    'opt_uid' => session('user.id'),
                    'status' => 2,
                ]);
                // 提交事务
                Db::commit();
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
        $this->success('处理提现失败完成','');
    }

    /**
     * 设置提现成功
     */
    public function ok() {              
        $ids = explode(',', $this->request->post('id', ''));
        $nowTime = time();
        foreach ($ids as $id) {
            //防止重复提现
            $aWithdrawal = Db::name($this->table)->where(['id' => $id, 'status' => 0])->find();
            if (!$aWithdrawal) {
                continue;
            }
//            //找出小程序用户信息
//            $aOpenInfo = Db::name('user_open_binds')->where(['user_id' => $aWithdrawal['uid'], 'openid_type' => 'wxapp'])->find();
//            $amount = (int) (0.01 * 100);
//            $this->_wxPay($aOpenInfo['openid'], $amount, $aWithdrawal['uid']);
//            
//            exit();
            Db::startTrans();
            try {
                //设置提现状态
                Db::name('app_withdrawal')->where(['id' => $id])->update([
                    'opt_time' => $nowTime,
                    'opt_uid' => session('user.id'),
                    'status' => 1,
                    'pay_time' => $nowTime,
                ]);
                Db::commit();
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
        $this->success('处理提现完成','');
    }
    
    public function test() {
        //找出小程序用户信息
        $aOpenInfo = Db::name('user_open_binds')->where(['user_id' => 8, 'openid_type' => 'wxapp'])->find();
        $amount = (int) (0.01 * 100);
        $this->_wxPay($aOpenInfo['openid'], $amount, 8);
        exit();
    }

    private function _wxPay($openId, $money, $userId) {
        // 3. 创建接口实例
        $wechat = new Pay(WxappConfig::getConfig());
        
        // 4. 组装参数，可以参考官方商户文档
        $options = [
            'partner_trade_no' => OrderService::createOrderSn($userId, $money, '提现打款'),
            'openid'           => $openId,
            'check_name'       => 'NO_CHECK',
            'amount'           => $money,
            'desc'             => '提现',
            'spbill_create_ip' => static::getAddress(),
        ];
        $result = $wechat->createTransfers($options);
        print_r($result);die;
    }

    /**
     * 读取微信客户端IP
     * @return null|string
     */
    static public function getAddress() {
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR') as $header) {
            if (!isset($_SERVER[$header]) || ($spoof = $_SERVER[$header]) === NULL) {
                continue;
            }
            sscanf($spoof, '%[^,]', $spoof);
            if (!filter_var($spoof, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $spoof = NULL;
            } else {
                return $spoof;
            }
        }
        return '0.0.0.0';
    }

}
