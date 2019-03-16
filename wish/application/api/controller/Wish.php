<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\UserService;
use think\Db;

/**
 * 用户api中心
 */
class Wish extends BaseController {

    /**
     * 
     * Array ( [total_day_nums] => 7 [total_nums] => 99 [today_need_money] => 100 [back_total_nums] => 9 )
     * @return type
     */
    public function getWishList() {
        $page = (int) $this->request->post('page', 1);
        $pageSize = (int) $this->request->post('pageSize', 10);
        $aWishList = Db::name('wish')->where(['status' => 1])->page($page, $pageSize)->order('create_time desc')->select();
        if (!$aWishList) {
            return $this->error('愿望为空');
        }
        foreach($aWishList as &$aWish){
            $aWish['fudai_shu'] = UserService::getWishFudaiNums($aWish['id']);
            $aUser = Db::name('users')->where(['id'=>$aWish['uid']])->find();
            $aWish['username'] = $aUser['username'];
            $aWish['img_url'] = $aUser['head_img_url'];
            $aWish['info'] = UserService::getWishNeedMoneyInfo($aWish['id']);
        }        
        $this->success('获取成功 星球星愿列表', '', $aWishList);
    }

}
