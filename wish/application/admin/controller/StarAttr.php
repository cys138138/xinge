<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class StarAttr extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'art_star_attr';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["title"]) && $get["title"] !== '') && $db->whereLike("title", "%{$get["title"]}%");
        
        if (isset($get['star_id']) && $get['star_id'] !== '') {
            $db->where(['star_id' => (int) $get['star_id']]);
            $aStar = Db::name('art_star')->where(['id'=>(int) $get['star_id']])->field('name')->find();
            if($aStar){
                $this->title = '[ '.$aStar['name'] . '].行权管理列表';
            }            
        }
        
        if (isset($get['time_length']) && $get['time_length'] !== '') {
            $db->where(['time_length' => (int) $get['time_length']]);            
        }
        
        if (isset($get['status']) && $get['status'] !== '') {
            $db->where(['status' => (int) $get['status']]);
            
        }
        
        
        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('create_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['update_time']) && $get['update_time'] !== '') {
            list($start, $end) = explode(' - ', $get['update_time']);
            $db->whereBetween('update_time', [strtotime($start), strtotime($end)]);
        }
        $db->order('sort desc,id desc');
        return parent::_list($db);
    }
    
    protected function _data_filter(&$param) {
        foreach($param as &$aItem){
            $aStar = Db::name('art_star')->where(['id'=>$aItem['star_id']])->field('name')->find();
            $aItem['star_name'] = $aStar['name'];
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
            $aDataStar_idIdList = Db::name('art_star')->select();
            $this->assign('aDataStar_idIdList', $aDataStar_idIdList);
            if (!isset($param['id'])) {
                if ($this->request->get('star_id','')) {
                    $param['star_id'] = (int)$this->request->get('star_id');
                }
            }
        }else {
            if (isset($param['id']) && $param['id']) {
                $param['update_time'] = time();
            } else {
                $param['update_time'] = time();
                $param['create_time'] = time();
            }
        }
    }

}
