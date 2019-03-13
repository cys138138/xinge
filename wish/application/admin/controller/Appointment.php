<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Appointment extends BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'art_star_appointment';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';
        list($get, $db) = [$this->request->get(), Db::name($this->table)];

        if (isset($get['star_id']) && $get['star_id'] !== '') {
            $db->where(['star_id' => (int) $get['star_id']]);
            $aStar = Db::name('art_star')->where(['id' => (int) $get['star_id']])->field('name')->find();
            if ($aStar) {
                $this->title = '[ ' . $aStar['name'] . ']行权';
            }
        }
        if (isset($get['attr_id']) && $get['attr_id'] !== '') {
            $db->where(['star_attr_id' => (int) $get['attr_id']]);
            $aAttr = Db::name('art_star_attr')->where(['id' => (int) $get['attr_id']])->field('title')->find();
            if ($aAttr) {
                $this->title .= '->[ ' . $aAttr['title'] . ']->预约列表';
            }
        }
        
        if (isset($get['status']) && $get['status'] !== '') {
            $db->where(['status' => (int) $get['status']]);            
        }
        if (isset($get['is_pay']) && $get['is_pay'] !== '') {
            $db->where(['is_pay' => (int) $get['is_pay']]);            
        }
         if (isset($get['appointment_city']) && $get['appointment_city'] !== '') {
            $db->whereLike("appointment_city", "%{$get["appointment_city"]}%");          
        }
        

        if (isset($get['appointment_time']) && $get['appointment_time'] !== '') {
            list($start, $end) = explode(' - ', $get['appointment_time']);
            $db->whereBetween('appointment_time', [strtotime($start), strtotime($end)]);
        }
        if (isset($get['appointment_end_time']) && $get['appointment_end_time'] !== '') {
            list($start, $end) = explode(' - ', $get['appointment_end_time']);
            $db->whereBetween('appointment_end_time', [strtotime($start), strtotime($end)]);
        }
        (isset($get["appointment_city"]) && $get["appointment_city"] !== '') && $db->whereLike("appointment_city", "%{$get["appointment_city"]}%");
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


    protected function _data_filter(&$param) {
        foreach($param as &$aItem){
            $aStar = Db::name('art_star')->where(['id'=>$aItem['star_id']])->field('name')->find();
            $aItem['star_name'] = $aStar['name'];
            $aTitle = Db::name('art_star_attr')->where(['id'=>$aItem['star_attr_id']])->field('title')->find();
            $aItem['star_attr_title'] = $aTitle['title'];
            $aUser = Db::name('user_open_binds')->where(['user_id'=>$aItem['uid']])->field('open_nickname')->find();
            $aItem['user_name'] = $aUser['open_nickname'];
        }
        
    }
    
    /**
     * 禁用
     */
    public function status()
    {
        if (DataService::update($this->table)) {
            $this->success("操作成功", '');
        }
        $this->error("操作失败，请稍候再试！");
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
            $aDataStar_attr_idIdList = Db::name('art_star_attr')->select();
            $this->assign('aDataStar_attr_idIdList', $aDataStar_attr_idIdList);
            $aDataUidUser_idList = Db::name('user_open_binds')->select();
            $this->assign('aDataUidUser_idList', $aDataUidUser_idList);
        }
    }

}
