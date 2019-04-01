<?php

namespace app\api\controller;

use app\api\lib\WxWebConfig;
use app\api\service\OrderService;
use controller\BasicAdmin;
use service\WechatService;
use think\Container;
use think\Db;
use WeChat\Pay;

/**
 * 艺人管理
 */
class Huser extends BasicAdmin {

    
    protected function getResponseType() {
        $config = Container::get('config');
        return $config->get('default_ajax_return');
    }
    
   /**
     * 网页授权测试
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function oauth()
    {
		$url = $this->request->get('cb',url("uinfo",'',true,true));
        $fans = WechatService::webOauth(1);
		return $this->getUserInfo($fans,urldecode($url));     
    }
	
	public function uinfo(){
		echo '<pre>';
		echo '这里是跳转指定后的页面 cookie 中的 h5_user_info';
		print_r(cookie('h5_user_info1'));
	}
	
	
	/**
     * 用户登录
     */
    public function getUserInfo($aUserI,$url) {
		if (!isset($aUserI['openid'])) {
            $this->error('解密数据出错了');
        }
		$pid = (int) $this->request->post('pid', 0);		
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
        setcookie('h5_user_info',  json_encode($aUserInfo),time()+ 60*60*48,'/');
		cookie('h5_user_info1', $aUserInfo);
		return $this->redirect($url);
    }
	
	
    public function getWxSign() {
        $url = $this->request->post('url', url('/index/index/index', null, true, true));
        $data = WechatService::webJsSDK($url);
        $this->success('ok', null, $data);
    }
	
	/**
     * 获取支付参数
     */
    public function getPaySign() {
        $userId = (int) $this->request->post('uid', 0);
        $wishId = (int) $this->request->post('wish_id', 0);
        if (!$userId || !$wishId) {
            return $this->error('ui 或者 wish_id为空');
        }
        $aLastWish = Db::name('wish')->where(['uid' => $userId, 'status' => 1, 'id' => $wishId])->find();
        if (!$aLastWish) {
            return $this->error('没有正在进行的愿望，请先创建。。');
        }
        //选择充值记录表id
        $money = (double) $aLastWish['one_money'];
        $aUserInfo = Db::name('user_open_binds')->where(['user_id' => $userId,'openid_type'=>'h5'])->find();
        if (!$aUserInfo) {
            return $this->error('找不到用户');
        }
        // 3. 创建接口实例
        $wechat = new Pay(WxWebConfig::getConfig());

        // 4. 组装参数，可以参考官方商户文档
        $options = [
            'body' => '稀有燃料',
            'out_trade_no' => OrderService::createOrderSn($userId, $money, '购买私有燃料'),
            'total_fee' => (int) ($money * 100), //金额转成分
            'openid' => $aUserInfo['openid'],
            'trade_type' => 'JSAPI',
            'attach' => $wishId,
            'notify_url' => url('@api/Notify/payCallback', '', true, true),
                //'spbill_create_ip' => '127.0.0.1',
        ];
        //获取预支付码
        $prepay = $wechat->createOrder($options);
        $result = $wechat->createParamsForJsApi($prepay['prepay_id']);
        return $this->success('获取成功', null, $result);
    }
	
}
