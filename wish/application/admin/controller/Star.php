<?php

namespace app\admin\controller;

use app\api\service\PurchaseService;
use app\api\service\StarService;
use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Star extends BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'art_star';

    /**
     * 列表
     */
    public function index() {
        $this->title = '艺人列表';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["name"]) && $get["name"] !== '') && $db->whereLike("name", "%{$get["name"]}%");
        (isset($get["code"]) && $get["code"] !== '') && $db->whereLike("code", "%{$get["code"]}%");
        (isset($get["type"]) && $get["type"] !== '') && $db->whereLike("type", "%{$get["type"]}%");
        (isset($get["purchase_price"]) && $get["purchase_price"] !== '') && $db->where("purchase_price", "{$get["purchase_price"]}");
        (isset($get["in_market_price"]) && $get["in_market_price"] !== '') && $db->where("in_market_price", "{$get["in_market_price"]}");
        (isset($get["cate_id"]) && $get["cate_id"] !== '') && $db->where("cate_id", "{$get["cate_id"]}");
        (isset($get["status"]) && $get["status"] !== '') && $db->where("status", "{$get["status"]}");
        (isset($get["time_length"]) && $get["time_length"] !== '') && $db->where("time_length", "{$get["time_length"]}");
        (isset($get["identity"]) && $get["identity"] !== '') && $db->whereLike("identity", "%{$get["identity"]}%");

        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('create_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['update_time']) && $get['update_time'] !== '') {
            list($start, $end) = explode(' - ', $get['update_time']);
            $db->whereBetween('update_time', [strtotime($start), strtotime($end)]);
        }
        $aDataCate_idIdList = Db::name('art_category')->select();
        $this->assign('aDataCate_idIdList', $aDataCate_idIdList);
        return parent::_list($db);
    }

    public function _data_filter(&$param) {
        foreach ($param as &$aItem) {
            $aData = Db::name('art_category')->where(['id' => $aItem['cate_id']])->find();
            $aItem['cate_name'] = $aData['name'];
        }
    }

    /**
     * 添加
     */
    public function add() {
        $this->title = '添加';
        return parent::add();
    }

    /**
     * 置顶
     */
    public function push() {
        Db::name($this->table)->where(['is_push' => 1])->update(['is_push' => 0]);
        if (DataService::update($this->table)) {
            $this->success("操作成功！", '');
        }
        $this->error("操作失败，请稍候再试！");
    }

    /**
     * 编辑
     */
    public function edit() {
        $this->title = '编辑';
        return parent::edit();
    }

    /**
     * 禁用
     */
    public function forbid() {
        return parent::forbid();
    }

    /**
     * 恢复
     */
    public function resume() {
        return parent::resume();
    }

    /**
     * 删除
     */
    public function del() {
        return parent::del();
    }

    /**
     * 格式化输出
     * @param type $param
     */
    protected function _form_filter(&$param) {
        if (!$this->request->isPost()) {
            if (isset($param['intro'])) {
                $param['intro'] = htmlspecialchars_decode($param['intro']);
            }
            if (isset($param['experience'])) {
                $param['experience'] = htmlspecialchars_decode($param['experience']);
            }
            if (isset($param['id'])) {
                $param['purchase_start_time'] = date('Y-m-d H:i:s', $param['purchase_start_time']);
                $param['purchase_end_time'] = date('Y-m-d H:i:s', $param['purchase_end_time']);
            }
            $aDataCate_idIdList = Db::name('art_category')->select();
            $this->assign('aDataCate_idIdList', $aDataCate_idIdList);
            $code = Db::name($this->table)->field('code')->order('code desc')->find();
            if ($code['code'] == 0) {
                $code['code'] = 100000;
            }
            $this->assign('code', $code['code'] + 1);
        } else {

            $param['purchase_start_time'] = strtotime($param['purchase_start_time']);
            $param['purchase_end_time'] = strtotime($param['purchase_end_time']);
            if (isset($param['id']) && $param['id']) {
                $param['update_time'] = time();
            } else {
                $param['update_time'] = time();
                $param['create_time'] = time();
            }
        }
    }

    /**
     * 根据申购情况自动分配票数
     */
    public function purchase() {
        set_time_limit(0);
        $starId = (int) $this->request->get('id', 0);
        $aStar = Db::name('art_star')->where(['id' => $starId])->find();
        if (!$aStar) {
            $this->error("找不到艺人信息。。");
        }
        if ($aStar['type'] != 1) {
            $this->error("状态不是申购中不允许操作。。");
        }
        $time = time();
        if ($aStar['purchase_start_time'] > $time) {
            $this->error("状态还未开始");
        }
        if ($aStar['purchase_end_time'] > $time) {
            $this->error("状态还未结束");
        }
        $aStarList = Db::name('art_star_purchase')->where(['star_id' => $starId, 'status' => 0])->select();
        $newData = [];
        //本次收到的申购总价
        $totalMoney = 0.00;
        //本次申购当前票数预定总价        
        $starTotalMoney = $aStar['time_length'] * $aStar['purchase_price'];
        $totalNums = 0;
        //汇总用户申购信息
        foreach ($aStarList as $aItem) {
            $bugNums = $aItem['buy_nums'];
            $realMoney = $aItem['real_money'];
            $purchase = $aItem['money'];
            $platform_commission = $aItem['platform_commission'];
            if (isset($newData[$aItem['uid']]['buy_nums'])) {
                $bugNums = $bugNums + $newData[$aItem['uid']]['buy_nums'];
                $realMoney = $realMoney + $newData[$aItem['uid']]['real_money'];
                $purchase = $purchase + $newData[$aItem['uid']]['money'];
                $platform_commission = $platform_commission + $newData[$aItem['uid']]['platform_commission'];
            }
            $newData[$aItem['uid']] = [
                'buy_nums' => $bugNums,
                'uid' => $aItem['uid'],
                'real_money' => $realMoney,
                'one_price' => $aItem['one_price'],
                'money' => $purchase, //计算申购总价
                'platform_commission' => $platform_commission, //计算所有手续费
            ];
            $totalMoney += $aItem['real_money'];
            $totalNums += $aItem['buy_nums'];
        }


        //本次总发行数量
        $totalSendNums = 0;
        //申购情况少于或者等于预设情况这时候可以全额发放票数
        if ($totalNums <= $aStar['time_length']) {
            //直接给用户申购全部数量
            foreach ($newData as $key => $aBuyItem) {
                //当前情况能分配所有票
                $precent = $aBuyItem['buy_nums'];
                //成功申购的价格
                $oneSuccessMoney = $precent * $aBuyItem['one_price'];
                $result = PurchaseService::sumPurchase($starId, $aBuyItem['uid'], $precent, $aBuyItem['buy_nums'], $oneSuccessMoney, $aBuyItem['platform_commission'], $aBuyItem['one_price'], $aBuyItem['money']);
                if ($result) {
                    //记录总申购分配票数
                    $totalSendNums += $precent;
                }
            }
            //记录本次汇总情况
            Db::name('art_star')->where(['id' => $starId])->update([
                'success_purchase_nums' => $totalSendNums,
                'type' => 2,
            ]);
            $this->success('处理完成');
        }
        //超额申购计算
        foreach ($newData as $key => $aBuyItem) {
            //拿到票占领总票的概率计算（不要4舍5入 不然出现实际发行大于申购数量）
            $precent = (int) (StarService::getPurchaseByMoney($totalMoney, $starTotalMoney, $aBuyItem['buy_nums']));
            //如果申购数量不足1 全额退
            if ($precent < 1) {
                //取消押金
                PurchaseService::cancelPurchase($aBuyItem['uid'], $starId);
                continue;
            }

            //成功申购的价格
            $oneSuccessMoney = $precent * $aBuyItem['one_price'];
            $result = PurchaseService::sumPurchase($starId, $aBuyItem['uid'], $precent, $aBuyItem['buy_nums'], $oneSuccessMoney, $aBuyItem['platform_commission'], $aBuyItem['one_price'], $aBuyItem['money']);
            if ($result) {
                //记录总申购分配票数
                $totalSendNums += $precent;
            }
        }
        //记录本次汇总情况
        Db::name('art_star')->where(['id' => $starId])->update([
            'success_purchase_nums' => $totalSendNums,
            'type' => 2,
        ]);
        $this->success('处理完成');
    }

}
