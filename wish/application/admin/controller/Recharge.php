<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Recharge extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'app_charge_option';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["title"]) && $get["title"] !== '') && $db->whereLike("title", "%{$get["title"]}%");
        (isset($get["status"]) && $get["status"] !== '') && $db->where("status", "{$get["status"]}");
        (isset($get["money"]) && $get["money"] !== '') && $db->where("money", "{$get["money"]}");
        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('create_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['update_time']) && $get['update_time'] !== '') {
            list($start, $end) = explode(' - ', $get['update_time']);
            $db->whereBetween('update_time', [strtotime($start), strtotime($end)]);
        }
        return parent::_list($db);
    }

    /**
     * 添加
     */
    public function add() {
        $this->title = '添加';
        return parent::add();
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
            
        } else {
            if (isset($param['id']) && $param['id']) {
                $param['update_time'] = time();
            } else {
                $param['update_time'] = time();
                $param['create_time'] = time();
            }
        }
    }

}
