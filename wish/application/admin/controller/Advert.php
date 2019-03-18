<?php

namespace app\admin\controller;

use think\Db;

/**
 * 控制器
 * @package app\admin\controller
 */
class Advert extends \controller\BasicAdmin
{

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'advert';

    /**
     * 列表
     */
    public function index()
    {
        $this->title = '列表';
        
            list($get, $db) = [$this->request->get(), Db::name($this->table)];
            (isset($get["title"]) && $get["title"] !== '') && $db->whereLike("title", "%{$get["title"]}%");
            return parent::_list($db);
    }

    
            /**
             * 添加
             */
            public function add()
            {
               $this->title = '添加';
               return parent::add();
            }

    
            /**
            * 编辑
            */
           public function edit()
           {
               $this->title = '编辑';
               return parent::edit();
           }

    
    
    
            /**
             * 删除
             */
            public function del()
            {
                return parent::del();
            }
        

    /**
    * 格式化输出
    * @param type $param
    */
   protected function _form_filter(&$param){
       if (!$this->request->isPost()) {
        
        
        
        
        }
    }
}
