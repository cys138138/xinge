<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use think\Db;

/**
 * 基本配置
 */
class Notice extends BaseController {

    /**
     * 获取文章
     */
    public function getNoticeList() {
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 10);
        $list = Db::name('art_notice')->where(['status' => 1, 'is_deleted' => 0])->order('sort desc,update_time desc')->page($page, $pageSize)->select();
        $this->dateFormater($list);
        return $this->success('获取成功1', null, $list);
    }
    
    /**
     * 获取公告详情
     */
    public function getNoticeDetail() {
        $id = (int) $this->request->get('id', 0);
        if(!$id){
            return $this->error('id参数缺失');
        }
        $aData = Db::name('art_notice')->where(['id' => $id, 'status' => 1, 'is_deleted' => 0])->find();
        $aData['create_time'] = date('Y-m-d H:i:s',$aData['create_time']);
        return $this->success('获取成功', null, $aData);
    }
    
    public function getGuize(){        
        $aData = Db::name('art_notice')->where(['status' => 1, 'is_deleted' => 0])->find();
        $aData['create_time'] = date('Y-m-d H:i:s',$aData['create_time']);
        $aData['desc'] = htmlspecialchars_decode($aData['desc']);
        return $this->success('获取规则成功', null, $aData);
    }
    
    public function getAdvert(){
        $aData = Db::name('advert')->find();
        $aData['create_time'] = date('Y-m-d H:i:s',$aData['create_time']);
        return $this->success('获取成功广告成功', null, $aData);
    }

}
