<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\lib\WxappConfig;
use app\api\service\JuheMessage;
use app\api\service\UserService;
use Exception;
use service\FileService;
use think\Db;
use WeMini\Crypt;
use WeMini\Qrcode;

/**
 * 用户api中心
 */
class User extends BaseController {

    /**
     * 设置用户身份证和真实姓名信息
     */
    public function setUserInfo() {
        $uid = (int) $this->request->post('uid', 0);
        $trueName = $this->request->post('true_name', '');
        $idno = $this->request->post('id_no', '');
        if (!$trueName || !$idno || !$uid) {
            $this->error('身份证号码和真实姓名必填uid');
        }
        $aUserInfo = Db::name('user_infos')->where(['user_id' => $uid])->find();
        if (!$aUserInfo) {
            $this->error('用户不存在');
        }
        Db::name('user_infos')
                ->where(['user_id' => $uid])
                ->update([
                    'id_true_name' => $trueName,
                    'id_no' => $idno,
        ]);
        $this->success('设置成功');
    }

    /**
     * 设置交易密码
     */
    public function setUserPayPassword() {
        $uid = (int) $this->request->post('uid', 0);
        $payPassword = $this->request->post('pay_password', '');
        if (!$payPassword || !$uid) {
            $this->error('交易密码必填');
        }
        if (strlen($payPassword) < 6) {
            $this->error('支付密码必须6位以上');
        }
        $aUserInfo = Db::name('user_infos')->where(['user_id' => $uid])->find();
        if (!$aUserInfo) {
            $this->error('用户不存在');
        }
        Db::name('user_infos')
                ->where(['user_id' => $uid])
                ->update([
                    'pay_password' => md5($payPassword),
        ]);
        $this->success('设置成功，请牢记交易密码');
    }
    /**
     * 用户登录
     */
    public function getUserInfo() {

        // 解码数据
        $iv = $this->request->post('iv', '');
        $code = $this->request->post('code', '');
        $decode = $this->request->post('decode', '');
        $pid = (int)$this->request->post('pid', 0);
        //$sessionKey = $this->request->post('session_key', 'OetNxl86B/yMpbwG6wtMEw==');
        if (!$iv || !$code || !$decode) {
            $this->error('参数错误');
        }

        $mini = new Crypt(WxappConfig::getConfig());
        $aSession = $mini->session($code);
        $sessionKey = isset($aSession['session_key']) ? $aSession['session_key'] : false;
        if (!$sessionKey) {
            $this->error('获取session_key 错误');
        }
        $aUserInfo = $mini->decode($iv, $sessionKey, $decode);
        if (!isset($aUserInfo['openId'])) {
            $this->error('解密数据出错了');
        }
        //判断账号
        $openId = $aUserInfo['openId'];
        $mUser = Db::name('user_open_binds')->where(['openid' => $openId])->find();
        $time = time();
        $userId = 0;

        //用户信息表
        $aUserOpenBinds = [
            'open_nickname' => $aUserInfo['nickName'],
            'openid' => $aUserInfo['openId'],
            'openid_type' => 'wxapp',
            'open_head' => $aUserInfo['avatarUrl'],
            'open_data' => json_encode($aUserInfo),
            'updatetime' => $time,
        ];
        //不存在则创建
        if (!$mUser) {
            //主表
            $aMainUser = [
                'status' => 1,
                'createtime' => $time,
                'updatetime' => $time,
            ];
            if ($pid) {
                $aMainUser['pid'] = $pid;
            }
            $userId = Db::name('users')->insertGetId($aMainUser);
            //用户信息表
            $aUserInfo = [
                'sex' => $aUserInfo['gender'], //用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
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
            //
        } else {
            $userId = $mUser['user_id'];
            Db::name('user_open_binds')->where(['user_id' => $userId])->update($aUserOpenBinds);
        }
        $aUser = Db::name('users')->where(['id'=>$userId])->find();
		$aUserInfo['is_bind_mobile'] = 0;
		if($aUser['mobile']){
			$aUserInfo['is_bind_mobile'] = 1;
		}
        $aUserInfo['userId'] = $userId;
        $this->success('用户ok', null, $aUserInfo);
    }

    /**
     * 获取用户推广二维码
     */
    public function getUserQr() {
        $userId = (int) $this->request->get('uid', 0);
        if (!$userId) {
            $this->error('uid缺失');
        }
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        if (!$aUser) {
            $this->error('出错了。。');
        }
        $aUserInfo = Db::name('user_open_binds')->where(['user_id' => $userId])->find();
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        if(!$aUser['is_have_share_auth']){
            $this->error('你所在的等级不能分享');
        }
        if ($aUserInfo['share_wx_app_qr_img']) {
            //$this->success('获取二维码成功', null, ['img_url' => $aUserInfo['share_wx_app_qr_img']]);
        }
        $mini = new Qrcode(WxappConfig::getConfig());
        $tag = false;
        $url = '';
        try {
            $content = $mini->createMiniScene('share_' . $userId, 'pages/start/start');
            $savePath = UserService::getQrShareKey($userId) . '_' . NOW_TIME;
            $result = FileService::save($savePath, $content);
            if (isset($result['url']) && $result['url']) {
                Db::name('user_open_binds')->where(['user_id' => $userId])->update([
                    'share_wx_app_qr_img' => $result['url'],
                    'updatetime' => NOW_TIME,
                ]);
            }
            $tag = true;
            $url = $result['url'];
        } catch (Exception $e) {
            $this->error('error' . $e->getMessage());
        }
        if (!$tag) {
            $this->error('发生未知错误');
        }
        $this->success('获取生成二维码成功', null, ['img_url' => $url]);
    }
    /**
     * 获取我邀请的人
     * @return type
     */
    public function getMyInvitationList() {
        $userId = (int) $this->request->get('uid', 0);
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 50);
        $count = Db::name('users')->where(['pid' => $userId, 'status' => 1])->count();
        $list['detail_list'] = Db::name('users')->where(['u.pid' => $userId, 'u.status' => 1])
                        ->alias('u')
                        ->field('uob.open_nickname as name,u.id,from_unixtime(u.createtime) as create_at')
                        ->leftJoin('user_open_binds uob', 'uob.user_id = u.id')
                        ->order('u.createtime desc')->page($page, $pageSize)->select();
        if (!$list['detail_list']) {
            $list['detail_list'] = [];
        }
        $list['count'] = $count;
        return $this->success('获取成功', null, $list);
    }
    
    /**
     * 发送短信验证码
     */
    public function getMsgcode() {
        $userId = (int) $this->request->post('uid', 0);
        $mobile = $this->request->post('mobile', '');
        if(!$mobile){
            return $this->error('手机号码不能为空');
        }
        $aUser = Db::name('users')->where(['mobile' => $mobile])->where('id !='. $userId)->find();
        if ($aUser) {
            return $this->error('手机号码已绑定其他账号');
        }

        $mCode = Db::name('app_mobile_code')->where(['uid' => $userId, 'mobile' => $mobile])->find();
        if ($mCode) {
            /**
             * 一分钟分钟之内不要重复发送
             */
            if ($mCode['status'] == 1 && (NOW_TIME - $mCode['create_time'] < 60)) {
                return $this->error('一分钟只能发送一次');
            }
        }
        $code = mt_rand(100000, 999999);
        $result = JuheMessage::sendCode($mobile, $code, $userId);
        if (!$result) {
            return $this->error('验证码发送失败，请重试');
        }
        return $this->success('发送成功，请留意短信');
    }
    
    /**
     * 验证验证码绑定手机
     */
    public function verifyMsgcode() {
        $userId = (int) $this->request->post('uid', 0);
        $mobile = $this->request->post('mobile', '');
        $code = $this->request->post('code', '');
        if(!$mobile || !$code){
            return $this->error('参数不能为空');
        }
        $aUser = Db::name('users')->where(['mobile' => $mobile])->where('id !='.$userId)->find();
        if ($aUser) {
            return $this->error('手机号码已绑定其他账号');
        }

        $mCode = Db::name('app_mobile_code')->where(['uid' => $userId, 'mobile' => $mobile, 'status' => 1])->find();
        if (!$mCode) {
            return $this->error('验证手机号码不存在');
        }
        /**
         * 验证码已经超时
         */
        if (NOW_TIME - $mCode['create_time'] > 600) {
//                return $this->error('验证码已经超时，请重新获取验证码');
        }
        if ($mCode['code'] != $code) {
            return $this->error('验证码错误！');
        }
        //绑定手机号
        Db::name('users')->where(['id' => $userId])->update(['mobile' => $mobile]);
        Db::name('app_mobile_code')->where(['uid' => $userId, 'mobile' => $mobile, 'status' => 1])->update(['status' => 2, 'update_time' => NOW_TIME]);
        return $this->success('成功绑定手机号码');
    }

}
