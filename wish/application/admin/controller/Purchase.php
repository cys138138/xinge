<?php

namespace app\admin\controller;

use think\Db;

/**
 * 申购信息
 * @package app\admin\controller
 */
class Purchase extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'art_star_purchase';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        if (isset($get['opt_time']) && $get['opt_time'] !== '') {
            list($start, $end) = explode(' - ', $get['opt_time']);
            $db->whereBetween('asp.opt_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('asp.create_time', [strtotime($start), strtotime($end)]);
        }
        
        (isset($get["star_name"]) && $get["star_name"] !== '') && $db->whereLike("star.name", "%{$get["star_name"]}%");
        (isset($get["uname"]) && $get["uname"] !== '') && $db->whereLike("ub.open_nickname", "%{$get["uname"]}%");
        (isset($get["one_price"]) && $get["one_price"] !== '') && $db->whereLike("asp.one_price", "%{$get["one_price"]}%");
        (isset($get["status"]) && $get["status"] !== '') && $db->where(['asp.status'=>(int)$get["status"]]);
        (isset($get["money"]) && $get["money"] !== '') && $db->where(['asp.money'=>$get["money"]]);
        (isset($get["real_money"]) && $get["real_money"] !== '') && $db->where(['asp.real_money'=>$get["real_money"]]);
        $db->alias('asp');
        $db->field('asp.*,ub.open_nickname,star.name as star_name');
        $db->leftJoin('user_open_binds ub', 'ub.user_id = asp.uid');
        $db->leftJoin('art_star star', 'star.id = asp.star_id');
        $db->order('asp.create_time desc');
        
        return parent::_list($db);
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
