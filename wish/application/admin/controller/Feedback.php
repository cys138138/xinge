<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Feedback extends \controller\BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'app_feedback';

    /**
     * 列表
     */
    public function index() {
        $this->title = '列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        (isset($get["uname"]) && $get["uname"] !== '') && $db->whereLike("ub.open_nickname", "%{$get["uname"]}%");
        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start, $end) = explode(' - ', $get['create_time']);
            $db->whereBetween('create_time', [strtotime($start), strtotime($end)]);
        }
        $db->field('app_feedback.*,ub.open_nickname');
        $db->leftJoin('user_open_binds ub','ub.user_id = app_feedback.uid');
		$db->order('app_feedback.create_time desc');
        return parent::_list($db);
    }
	
	/**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_data_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['img_list'] = explode(',',$vo['attache']);
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
