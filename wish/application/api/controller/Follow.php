<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use think\Db;

/**
 * 关注控制器
 */
class Follow extends BaseController {

    /**
     * 获取关注列表
     */
    public function getFollowList() {
        $uid = (int) $this->request->get('uid', 0);
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 10);
        $list = Db::name('user_follow_star')
                ->where(['follow.uid' => $uid])
                ->field('follow.*,star.name as star_name,star.code,star.thumb_img')
                ->leftJoin('art_star star', 'follow.star_id = star.id')
                ->alias('follow')
                ->page($page, $pageSize)
                ->select();
        $list = array_map(function($aItem) {
            $aItem['today_price'] = StarService::getStarPrice($aItem['star_id']);
            $aItem['zhangdie'] = StarService::getZD($aItem['star_id']);
            return $aItem;
        }, $list);
        if (!$list) {
            $this->success('暂无数据', null, []);
        }
        return $this->success('获取成功', null, $list);
    }

    /**
     * 添加关注
     */
    public function addFollow() {
        $uid = (int) $this->request->post('uid', 0);
        $startId = (int) $this->request->post('star_id', 0);
        if(!$startId){
            $this->error('艺人id 不能为空');
        }
        $result = Db::name('user_follow_star')
                ->where(['uid' => $uid, 'star_id' => $startId])
                ->count();
        if ($result) {
            return $this->success('已经成功关注，无需重复关注');
        }
        Db::name('user_follow_star')->insertGetId([
            'uid' => $uid,
            'star_id' => $startId,
            'create_time' => time(),
        ]);
        return $this->success('成功关注');
    }

    /**
     * 取消关注
     */
    public function noFollow() {
        $uid = (int) $this->request->post('uid', 0);
        $startId = (int) $this->request->post('star_id', 0);
        if(!$startId){
            $this->error('艺人id 不能为空');
        }
        $result = Db::name('user_follow_star')
                ->where(['uid' => $uid, 'star_id' => $startId])
                ->count();
        if (!$result) {
            return $this->success('还没关注呢');
        }
        Db::name('user_follow_star')->where(['uid' => $uid, 'star_id' => $startId])->delete();
        return $this->success('成功取消关注');
    }

    /**
     * 获取我是否关注这个明星
     */
    public function getIsFollow() {
        $uid = (int) $this->request->get('uid', 0);
        $startId = (int) $this->request->get('star_id', 0);

        $result = Db::name('user_follow_star')
                ->where(['uid' => $uid, 'star_id' => $startId])
                ->count();
        $isFollow = 0;
        if ($result) {
            $result = 1;
        }
        return $this->success('is_follow 0 没关注 1 已经关注', null, ['is_follow' => $result]);
    }

}
