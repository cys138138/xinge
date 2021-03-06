<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class HomeUser extends BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'users';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["mobile"]) && $get["mobile"] !== '') && $db->whereLike("user.mobile", "%{$get["mobile"]}%");
        (isset($get["uname"]) && $get["uname"] !== '') && $db->whereLike("ub.open_nickname", "%{$get["uname"]}%");
        
        (isset($get["true_name"]) && $get["true_name"] !== '') && $db->whereLike("uinfo.id_true_name", "%{$get["true_name"]}%");
        (isset($get["idno"]) && $get["idno"] !== '') && $db->whereLike("uinfo.id_no", "%{$get["idno"]}%");
        (isset($get["money"]) && $get["money"] !== '') && $db->whereLike("user.money", "%{$get["money"]}%");
        (isset($get["status"]) && $get["status"] !== '') && $db->where(['user.status'=>(int)$get["status"]]);
        (isset($get["pid"]) && $get["pid"] !== '') && $db->where(['user.pid'=>(int)$get["pid"]]);
        (isset($get["is_have_share_auth"]) && $get["is_have_share_auth"] !== '') && $db->where(['user.is_have_share_auth'=>(int)$get["is_have_share_auth"]]);
        
        if (isset($get['createtime']) && $get['createtime'] !== '') {
            list($start, $end) = explode(' - ', $get['createtime']);
            $db->whereBetween('user.createtime', [strtotime($start), strtotime($end)]);
        }
        
        $db->alias('user');
        $db->field('user.*,ub.open_nickname,uinfo.id_no,uinfo.id_true_name');
        $db->leftJoin('user_open_binds ub', 'ub.user_id = user.id');
        $db->leftJoin('user_infos uinfo', 'uinfo.user_id = user.id');
        $db->order('user.money desc,user.createtime desc');
        
        return parent::_list($db);
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
     * 格式化输出
     * @param type $param
     */
    protected function _form_filter(&$param) {
        if (!$this->request->isPost()) {
            
        }
    }
    
    public function shareauth()
    {
        if (DataService::update($this->table)) {
            $this->success("设置成功！", '');
        }
        $this->error("设置失败，请稍候再试！");
    }

}
