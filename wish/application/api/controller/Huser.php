<?php

namespace app\api\controller;

use think\Db;
use service\WechatService;
use controller\BasicAdmin;

/**
 * 艺人管理
 */
class Huser extends BasicAdmin {

   /**
     * 网页授权测试
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function oauth()
    {
		$url = $this->request->get('cb','http://baidu.com');
        $fans = WechatService::webOauth(1);
		return $this->getUserInfo($fans,urldecode($url));     
    }
	
	
	/**
     * 用户登录
     */
    public function getUserInfo($aUserI,$url) {
		if (!isset($aUserI['openid'])) {
            $this->error('解密数据出错了');
        }		
		$aUserInfo = $aUserI['fansinfo'];
		$aUserInfo['openid'] = $aUserI['openid'];
        //判断账号
        $openId = $aUserInfo['openid'];
        $mUser = Db::name('user_open_binds')->where(['openid' => $openId])->find();
        $time = time();
        $userId = 0;
		
        //用户信息表
        $aUserOpenBinds = [
            'open_nickname' => $aUserInfo['nickname'],
            'openid' => $aUserInfo['openid'],
            'openid_type' => 'h5',
            'open_head' => $aUserInfo['headimgurl'],
            'open_data' => json_encode($aUserInfo),
            'updatetime' => $time,
        ];
        //不存在则创建
        if (!$mUser) {			
			$unionId = isset($aUserInfo['unionid']) ? $aUserInfo['unionid'] : '';
			//看看有没存在$unionId
			if($unionId){
				$aOneMain = Db::name('users')->where(['unionId'=>$unionId])->find();
				if($aOneMain){
					$userId = $aOneMain['id'];
				}else{
					//都不存在则创建
					$aMainUser = [
						'status' => 1,
						'username' => $aUserInfo['nickname'],
						'head_img_url' => $aUserInfo['headimgurl'],
						'createtime' => $time,
						'updatetime' => $time,
						'unionId'=> $unionId,
					];
					if ($pid) {
						$aMainUser['pid'] = $pid;
					}
					$userId = Db::name('users')->insertGetId($aMainUser);
				}
			}else{
				//都不存在则创建
				$aMainUser = [
					'status' => 1,
					'username' => $aUserInfo['nickname'],
					'head_img_url' => $aUserInfo['headimgurl'],
					'createtime' => $time,
					'updatetime' => $time,
					'unionId'=> $unionId,
				];
				if ($pid) {
					$aMainUser['pid'] = $pid;
				}
				$userId = Db::name('users')->insertGetId($aMainUser);
			}			
            
            //用户信息表
            $aUserInfo = [
                'sex' => $aUserInfo['sex'], //用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
                'contact_province' => $aUserInfo['province'],
                'contact_city' => $aUserInfo['city'],
                'contact_country' => $aUserInfo['country'],
                'createtime' => $time,
                'updatetime' => $time,
                'user_id' => $userId,
            ];
            Db::name('user_infos')->insert($aUserInfo);
            //绑定信息表
            $aUserOpenBinds['user_id'] = $userId;
            $aUserOpenBinds['createtime'] = $time;
            Db::name('user_open_binds')->insert($aUserOpenBinds);
            $aUserInfo['mobile'] = '';
            $aUserInfo['sign'] = '';
        } else {

            $aMainU = Db::name('users')->find($mUser['user_id']);
            $aUserInfo['nickname'] = $aMainU['username'] ? $aMainU['username'] : $aUserInfo['nickname'];
            $aUserInfo['mobile'] = $aMainU['mobile'] ? $aMainU['mobile'] : '';
            $aUserInfo['sign'] = $aMainU['sign'] ? $aMainU['sign'] : '';
            $userId = $mUser['user_id'];
            Db::name('users')->where(['id' => $aMainU['id']])->update([
                'head_img_url' => $aUserOpenBinds['open_head'],
				'unionId'=>$aUserInfo['unionid'],
            ]);
            Db::name('user_open_binds')->where(['user_id' => $userId,'openid_type' => 'wxapp'])->update($aUserOpenBinds);
        }
        $aUserInfo['userId'] = $userId;
		
		
		$aUserInfo['openId'] = $aUserInfo['openid'];
		$aUserInfo['nickName'] = $aUserInfo['nickname'];
		$aUserInfo['unionId'] = $aUserInfo['unionid'];
		$aUserInfo['gender'] = $aUserInfo['sex'];
		$aUserInfo['avatarUrl'] = $aUserInfo['headimgurl'];
		$aUserInfo['sign'] = $aMainU['sign'] ? $aMainU['sign'] : '';
		unset($aUserInfo['openid']);
		unset($aUserInfo['nickname']);
		unset($aUserInfo['unionid']);
		unset($aUserInfo['sex']);
		unset($aUserInfo['headimgurl']);
		cookie('h5_user_info', $aUserInfo);
		return $this->redirect($url);
    }
}
