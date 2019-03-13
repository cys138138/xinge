<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use think\Db;

/**
 * 基本配置
 */
class Article extends BaseController {

    /**
     * 获取文章
     */
    public function getArticleList() {
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 10);
        $list = Db::name('art_article')->where(['status' => 1, 'is_deleted' => 0])->order('sort desc,update_time desc')->page($page, $pageSize)->select();
        $this->dateFormater($list);
        return $this->success('获取成功1', null, $list);
    }
    
    /**
     * 获取文章详情
     */
    public function getArticleDetail() {
        $id = (int) $this->request->get('id', 0);
        if(!$id){
            return $this->error('id参数缺失');
        }
        $aData = Db::name('art_article')->where(['id' => $id, 'status' => 1, 'is_deleted' => 0])->find();
        $aData['create_time'] = date('Y-m-d H:i:s',$aData['create_time']);
        return $this->success('获取成功', null, $aData);
    }

}
