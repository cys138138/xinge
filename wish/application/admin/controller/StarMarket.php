<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class StarMarket extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'art_star_market';

    /**
     * 列表
     */
    public function index() {
        $this->title = '买卖市场数据列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];

        if (isset($get['star_id']) && $get['star_id'] !== '') {
            $db->where(['star_id' => (int) $get['star_id']]);
            $aStar = Db::name('art_star')->where(['id'=>(int) $get['star_id']])->field('name')->find();
            if($aStar){
                $this->title = '[ '.$aStar['name'] . ']';
            }            
        }
        if (isset($get['type']) && $get['type'] !== '') {
            $db->where(['type' => (int) $get['type']]);
            $this->title .= (int) $get['type'] ? '卖方市场' : '买方市场';
        }
        
        if (isset($get['status']) && $get['status'] !== '') {
            $db->where(['status' => (int) $get['status']]);
            
        }
        if (isset($get['is_cancel']) && $get['is_cancel'] !== '') {
            $db->where(['is_cancel' => (int) $get['is_cancel']]);
            
        }
        
        if (isset($get['is_success']) && $get['is_success'] !== '') {
            $db->where(['is_success' => (int) $get['is_success']]);
            
        }
        
        if (isset($get['uid']) && $get['uid'] !== '') {
            $db->where(['uid' => (int) $get['uid']]);
            
        }
        
        
        if (isset($get['time_length']) && $get['time_length'] !== '') {
            $db->where(['time_length' => (int) $get['time_length']]);
            
        }

        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('create_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['update_time']) && $get['update_time'] !== '') {
            list($start, $end) = explode(' - ', $get['update_time']);
            $db->whereBetween('update_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['buy_time']) && $get['buy_time'] !== '') {
            list($start, $end) = explode(' - ', $get['buy_time']);
            $db->whereBetween('buy_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['cancel_time']) && $get['cancel_time'] !== '') {
            list($start, $end) = explode(' - ', $get['cancel_time']);
            $db->whereBetween('cancel_time', [strtotime($start), strtotime($end)]);
        }
        return parent::_list($db);
    }
    
    protected function _data_filter(&$param) {
        foreach($param as &$aItem){
            $aStar = Db::name('art_star')->where(['id'=>$aItem['star_id']])->field('name')->find();
            $aItem['star_name'] = $aStar['name'];
            $aUser = Db::name('user_open_binds')->where(['user_id'=>$aItem['uid']])->field('open_nickname')->find();
            $aItem['user_name'] = $aUser['open_nickname'];
        }
        
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
            
        }
    }

}
