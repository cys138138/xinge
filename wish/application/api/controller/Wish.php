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

	/**
     * 添加星愿记录
     */
    public function addWishLog() {
        $uid = (int) $this->request->post('uid', 0);
        $wishId = $this->request->post('wish_id', 0);
        $content = $this->request->post('content', '测试');
        $bs64 = $this->request->post('bs64', '');
        if(!$content){
            return $this->error('内容为空');
        }

        $feedback = Db::name('wish')->where(['uid' => $uid,'id'=>$wishId])->find();
		if(!$feedback){
			return $this->error('非法参数');
		}
        Db::name('wish_log')->insert([
            'create_time' => time(),
            'create_date' => date('Y-m-d'),
            'wish_id' => $wishId,
            'content' => $content,
            'attache' => $bs64,
        ]);
        return $this->success('记录成功');
    }
	
	/**
     * 添加星愿记录
     */
    public function editorWishLog() {
        $uid = (int) $this->request->post('uid', 0);
        $id = $this->request->post('id', 0);
        $content = $this->request->post('content', '测试');
        $bs64 = $this->request->post('bs64', '');
        if(!$content){
            return $this->error('内容为空');
        }

        $feedback = Db::name('wish_log')->where(['id'=>$id])->find();
		if(!$feedback){
			return $this->error('非法参数');
		}
        Db::name('wish_log')->where(['id'=>$id])->update([
            'update_time' => time(),
            'content' => $content,
            'attache' => $bs64,
        ]);
        return $this->success('修改成功');
    }
	
	/**
     * 删除记录
     */
    public function delWishLog() {
        $uid = (int) $this->request->post('uid', 0);
        $id = $this->request->post('id', 0);

        $feedback = Db::name('wish_log')->where(['id'=>$id])->find();
		if(!$feedback){
			return $this->error('非法参数');
		}
        Db::name('wish_log')->where(['id'=>$id])->delete();
        return $this->success('删除成功');
    }
	
	/**
     * 获取详情记录
     */
    public function getWishLog() {
        $uid = (int) $this->request->post('uid', 0);
        $id = $this->request->post('id', 0);

        $feedback = Db::name('wish_log')->where(['id'=>$id])->find();
		if(!$feedback){
			return $this->error('非法参数');
		}
        return $this->success('删除成功',null,$feedback);
    }
	
	/**
     * 获取星愿记录
     */
    public function getWishLogList() {
        $wishId = $this->request->post('wish_id', 74);
        $feedback = Db::name('wish_log')->where(['wish_id'=>$wishId])->order("create_time desc")->select();
		$days = Db::name('wish_log')->where(['wish_id'=>$wishId])->group("create_date")->count();
		if(!$feedback){
			return $this->success('获取成功',null,[]);
		}
        foreach($feedback as &$v){
            $v['attache'] = explode(',', $v['attache']);
        }
		$result = [
			'list'=>$feedback,
			'days'=>$days,
		];
        return $this->success('获取成功 days 坚持了多少天',null,$result);
    }
}
