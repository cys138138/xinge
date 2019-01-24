<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Task extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'xin_share_task';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["title"]) && $get["title"] !== '') && $db->whereLike("title", "%{$get["title"]}%");
        $db->order('create_at desc');
        return parent::_list($db);
    }
    
    protected function _data_filter(&$data){
        foreach ($data as &$aItem) {
            $aItem['uv'] = Db::name('xin_share_pv_uv')->where(['t_id'=>$aItem['id']])->group('t_id')->count();
            $aItem['pv'] = Db::name('xin_share_pv_uv')->where(['t_id'=>$aItem['id']])->count();
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
     * 编辑
     */
    public function edit() {
        $this->title = '编辑';
        return parent::edit();
    }

    /**
     * 删除
     */
    public function del() {
        return parent::del();
    }
    
    /**
     * address
     */
    public function address() {
        $id = $this->request->get('id',0);
        return "<div style='margin:50px;'> <input style='width:80%;height:50px;' value='". url("/index/index/index",null,true,true) ."?ss_id=". $id ."'/> </div>";
    }

    /**
     * 格式化输出
     * @param type $param
     */
    protected function _form_filter(&$param) {
        if (!$this->request->isPost()) {
            
        }else {
            if (isset($param['id']) && $param['id']) {
                
            } else {
                $param['create_at'] = date('Y-m-d H:i:s');
            }
            $param['update_time'] = time();
        }
    }

}
