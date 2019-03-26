<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\lib\WxappConfig;
use app\api\service\JuheMessage;
use app\api\service\OrderService;
use app\api\service\UserService;
use Endroid\QrCode\QrCode as QrC;
use Exception;
use service\FileService;
use think\Db;
use WeChat\Pay;
use WeMini\Crypt;
use WeMini\Qrcode;

/**
 * 用户api中心
 */
class User extends BaseController {

    /**
     * 设置用基础信息
     */
    public function setUserInfo() {
        $uid = (int) $this->request->post('uid', 0);
        $username = $this->request->post('username', '');
        $mobile = $this->request->post('mobile', '');
        $sign = $this->request->post('sign', '');
        if ($mobile) {
            if (!preg_match("/^1\d{10}$/", $mobile)) {
                $this->error('手机号码不合法');
            }
        }

        $aUserInfo = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUserInfo) {
            $this->error('用户不存在');
        }
        Db::name('users')
                ->where(['id' => $uid])
                ->update([
                    'username' => $username,
                    'mobile' => $mobile,
                    'sign' => $sign,
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

    public function getUserBaseInfo() {
        $uid = (int) $this->request->post('uid', 4);
        if (!$uid) {
            $this->error('用户id必填');
        }
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            $this->error('用户不存在');
        }
	    $aWish = Db::name('wish')->where(['uid' => $aUser['id'], 'status' => 1])->find();
	    $number = '';
	if ($aWish) {
		$number = $aWish['number'];	
	}
	   $aUser['number'] = $number; 
        unset($aUser['password']);
        $this->success('获取成功', null, $aUser);
    }

    /**
     * 用户登录
     */
    public function getUserInfo() {

        // 解码数据
        $iv = $this->request->post('iv', '');
        $code = $this->request->post('code', '');
        $decode = $this->request->post('decode', '');
        $pid = (int) $this->request->post('pid', 0);
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
                'username' => $aUserInfo['nickName'],
                'head_img_url' => $aUserInfo['avatarUrl'],
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
            $aUserInfo['mobile'] = '';
            $aUserInfo['sign'] = '';
        } else {

            $aMainU = Db::name('users')->find($mUser['user_id']);
            $aUserInfo['nickName'] = $aMainU['username'] ? $aMainU['username'] : $aUserInfo['nickName'];
            $aUserInfo['mobile'] = $aMainU['mobile'] ? $aMainU['mobile'] : '';
            $aUserInfo['sign'] = $aMainU['sign'] ? $aMainU['sign'] : '';
            $userId = $mUser['user_id'];
            Db::name('users')->where(['id' => $aMainU['id']])->update([
                'head_img_url' => $aUserOpenBinds['open_head'],
            ]);
            Db::name('user_open_binds')->where(['user_id' => $userId])->update($aUserOpenBinds);
        }
        $aUserInfo['userId'] = $userId;
        $this->success('用户ok', null, $aUserInfo);
    }

    /**
     * 获取用户推广二维码
     */
    public static function getUserQr($userId) {
        $aUser = Db::name('users')->where(['id' => $userId])->find();
        if ($aUser['share_wx_app_qr_img']) {
            return $aUser['share_wx_app_qr_img'];
        }
        $mini = new Qrcode(WxappConfig::getConfig());
        $tag = false;
        $url = '';
        try {
            $content = $mini->createMiniScene('share_' . $userId, 'pages/index/index');
            $savePath = UserService::getQrShareKey($userId) . '_' . NOW_TIME;
            $result = FileService::save($savePath, $content);
            if (isset($result['url']) && $result['url']) {
                Db::name('users')->where(['id' => $userId])->update([
                    'share_wx_app_qr_img' => $result['url'],
                    'updatetime' => NOW_TIME,
                ]);
            }
            $tag = true;
            $url = $result['url'];
        } catch (Exception $e) {
            return false;
        }
        if (!$tag) {
            return false;
        }

        return $url;
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
        if (!$mobile) {
            return $this->error('手机号码不能为空');
        }
        $aUser = Db::name('users')->where(['mobile' => $mobile])->where('id !=' . $userId)->find();
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
        if (!$mobile || !$code) {
            return $this->error('参数不能为空');
        }
        $aUser = Db::name('users')->where(['mobile' => $mobile])->where('id !=' . $userId)->find();
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

    /**
     * 
     * Array ( [total_day_nums] => 7 [total_nums] => 99 [today_need_money] => 100 [back_total_nums] => 9 )
     * @return type
     */
    public function getWish() {
        $userId = (int) $this->request->post('uid', 4);
        $aWish = Db::name('wish')->where(['uid' => $userId, 'status' => 1])->find();
        if (!$aWish) {
            return $this->error('愿望不存在，请先创建愿望');
        }
        //计算
        $target_money = $aWish['target_money'];
        $okMoney = UserService::getWishTotalMoney($aWish['id']);
        if ($target_money <= $okMoney) {
            return $this->error('愿望实现，开启新的愿望吧');
        }
        $aData = UserService::getWishNeedMoneyInfo($aWish['id']);
	$aData['wish_id'] = $aWish['id'];
        $this->success('获取成功 ：total_day_nums 已经生存天数 total_nums 总共存了多少桶 today_need_money 今天需要存的桶数 back_total_nums 还需要几天返回', '', $aData);
    }

    /**
     * uid 用户id
     * title 愿望标题
     * target_money 愿望目标金额
     * target_type 愿望时间类型 1 按天 2按周 3 按月
     * one_money 如果 target_type 是1 则这里是每天需要存的金额 target_type 是 2  则这里是每周需要存的金额 target_type 是3  则这里是每月需要存的金额 
     * @return type
     */
    public function createWish() {
        $userId = (int) $this->request->post('uid', 4);
        $title = $this->request->post('title', '去玩');
        $target_money = (int) $this->request->post('target_money', 88);
        $target_type = (int) $this->request->post('target_type', 1);
        $one_money = (int) $this->request->post('one_money', 8);
        if (!$title) {
            return $this->error('愿望名称必填');
        }
        if (!$target_money) {
            return $this->error('愿望目标必填');
        }
        if (!$target_type) {
            return $this->error('时间节点必选');
        }
        if (!$one_money) {
            return $this->error('愿望时间单位必填');
        }

        if ($target_money < 1) {
            return $this->error('愿望目标必须大于1元');
        }
        if ($one_money < 1) {
            return $this->error('愿望金额必须大于1元');
        }
        $aWish = Db::name('wish')->where(['uid' => $userId, 'status' => 1])->find();
        if ($aWish) {
            return $this->error('还有未完成的愿望呢！请先完成');
        }

        $aData = [
            'uid' => $userId,
            'title' => $title,
            'target_money' => $target_money,
            'target_type' => $target_type,
            'one_money' => $one_money,
            'create_time' => NOW_TIME,
            'status' => 1,
            'number' => date('mHi') . mt_rand(1000, 9999),
        ];
        if ($id = Db::name('wish')->insertGetId($aData)) {
            $aData = UserService::getWishNeedMoneyInfo($id);
            $this->success('创建成功 ：total_day_nums 已经生存天数 total_nums 总共存了多少桶 today_need_money 今天需要存的桶数 back_total_nums 还需要几天返回', '', $aData);
        }
        return $this->error('出错了重试。。');
    }

    /**
     * 获取愿望详情
     */
    public function getMyWishInfo() {
        $wishId = (int) $this->request->post('wish_id', 0);
        $uid = (int) $this->request->post('uid', 0);
        if (!$wishId) {
            $aLastWish = Db::name('wish')->where(['uid' => $uid, 'status' => 1])->find();
            if (!$aLastWish) {
                return $this->error('愿望不存在，请先创建愿望');
            }
            $wishId = $aLastWish['id'];
        } else {
            $aLastWish = Db::name('wish')->where(['id' => $wishId, 'status' => 1])->find();
            $uid = $aLastWish['uid'];
        }
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        $aData['head_img'] = $aUser['head_img_url'];
        $aData['wish_id'] = $wishId;
        $aData['login_date'] = date('Y-m-d', $aLastWish['create_time']);
        $aData = array_merge($aData, $this->_getWishBaseInfo($wishId));
        $aData['u_name'] = $aUser['username'];
        $this->success('获取成功 ：shengcun_nums 已经生存天数 qifu_nums 祈福数量 fudai_nums 福袋数量', '', $aData);
    }

    private function _getWishBaseInfo($wishId) {
        $aData['shengcun_nums'] = (int) UserService::getWishTotalDay($wishId);
        $aData['qifu_nums'] = (int) UserService::getWishQifuNums($wishId);
        $aData['fudai_nums'] = (int) UserService::getWishFudaiNums($wishId);
        $aData['wish_number'] = (int) UserService::getWishNumber($wishId);
        $aWishInfo = UserService::getWishNeedMoneyInfo($wishId);
        $aData['need_jianchi_day'] = $aWishInfo['back_total_nums'];
        return $aData;
    }
    
    public function getShareImage(){        
        $wishId = (int)$this->request->get('wish_id',22);
        $aLastWish = Db::name('wish')->where(['id' => $wishId])->find();
        if(!$aLastWish){
            $this->error("梦想暂时不见了..");
        }
        $aUser = Db::name('users')->where(['id' => $aLastWish['uid']])->find();
        if(!$aUser){
            $this->error("梦想人暂时不见了..");
        }
	    if($aLastWish['status'] != 1){
		    $this->error("梦想状态不可分享..");
	    }
        $aData = $this->_getWishBaseInfo($wishId);
        $posX = 136;
        $posY = 736;        
        $w = 116;
        $h = 118;
        $shengcuntianshu = $aData['shengcun_nums'];
        $qifurenshu = $aData['qifu_nums'];
        $fudaishu = $aData['fudai_nums'];
        $number = $aLastWish['number'];
        $uname = $aUser['username'];
		$heading = $aUser['head_img_url'] ? $aUser['head_img_url'] : APP_PATH.'/../132.png';
        $date = date('Y.m.d',$aLastWish['create_time']);
        $tian = $aData['need_jianchi_day'].'/天';
        
        $qrcodeImgUrl = static::getUserQr($aLastWish['uid']);
        
       $qrcodeImg = $this->imgzip($qrcodeImgUrl, 120, 120);
        
//        $qrcodeImg = imagecreatefromstring(file_get_contents($qrcodeImgUrl));   
		$bgImgUrl = APP_PATH.'/../bg3.png';
        $bigImg = imagecreatefrompng($bgImgUrl);
        
        $black = imagecolorallocate($bigImg, 250, 250, 255);//字体颜色
        $font = APP_PATH.'/../Arial.ttf';//字体路径
        $font2 = APP_PATH.'/../bista.ttf';//字体路径
        imagefttext($bigImg, 48, 0, 80, 340, $black, $font, $shengcuntianshu);
        imagefttext($bigImg, 48, 0, 300, 340, $black, $font, $qifurenshu);
        imagefttext($bigImg, 48, 0, 500, 340, $black, $font, $fudaishu);
        imagefttext($bigImg, 14, 0, 350, 484, $black, $font, $number);
        $black2 = imagecolorallocate($bigImg, 102, 87, 72);//字体颜色
        imagefttext($bigImg, 20, 0, 390, 760, $black2, $font2, $uname);
        imagefttext($bigImg, 20, 0, 400, 806, $black2, $font2, $date);
        imagefttext($bigImg, 20, 0, 420, 843, $black2, $font2, $tian);
		$imageSize = getimagesize($bgImgUrl);
		$baseImg = imagecreatetruecolor($imageSize[0], $imageSize[1]); 
		//2.上色 
		$color=imagecolorallocate($baseImg,255,0,0); 
		
		
        imagecopymerge($baseImg, $bigImg, 0, 0, 0, 0, $imageSize[0], $imageSize[1], 100);
		$avater = imagecreatefromstring(file_get_contents($heading));
		$wAva = getimagesize($heading);
		$w11 = $wAva[0];
        $h11 = $wAva[1];
		//画圆弧
        imagearc($avater, $w11/2, $h11/2, $w11, $h11, 0, 360, $color);
        imagefilltoborder($avater, 0, 0, $color, $color); 
        imagefilltoborder($avater, $w, 0, $color, $color); 
        imagefilltoborder($avater, 0, $h11, $color, $color); 
        imagefilltoborder($avater, $w11, $h11, $color, $color);
		//将$color 设置成透明
		imagecolortransparent($avater,$color); 
		imagefill($avater,0,0,$color);
		
		imagecopymerge($baseImg, $avater, 275, 560, 0, 0, $wAva[0], $wAva[1], 100);
		imagecolortransparent($baseImg,$color); 
		imagefill($baseImg,0,0,$color);
		imagecopymerge($baseImg, $qrcodeImg, $posX, $posY, 0, 0, $w, $h, 100);
		//3.输出之前设置指定颜色透明 
		imagecolortransparent($baseImg,$color); 
		imagefill($baseImg,0,0,$color);
		
		header('Content-type:image/png');   
        imagepng($baseImg);die;             
    }
    
    public function imgzip($src, $newwid, $newhei) {
        $imgInfo = getimagesize($src);
        $imgType = image_type_to_extension($imgInfo[2], false);

        //声明图片   打开图片 在内存中
        $image = imagecreatefromstring(file_get_contents($src));
        //方便配置长度宽度、高度，设置框为变量wid,高度为hei
        $wid = $imgInfo[0];
        $hei = $imgInfo[1];
        //判断长度和宽度，以方便等比缩放,规格按照500, 320
        if ($wid > $hei) {
            $wid = $newwid;
            $hei = $newwid / ($wid / $hei);
        } else {
            $wid = $newhei * ($wid / $hei);
            $hei = $newhei;
        }
        //在内存中建立一张图片
        $images2 = imagecreatetruecolor($newwid, $newhei); //建立一个500*320的图片
        //将原图复制到新建图片中
        //imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)

        imagecopyresampled($images2, $image, 0, 0, 0, 0, $wid, $hei, $imgInfo[0], $imgInfo[1]);

        //销毁原始图片
        imagedestroy($image);
        return $images2;
    }

    public function yuanjiao($imgpath) {
        $src_img = null;
        $ext = pathinfo($imgpath);
        switch ($ext['extension']) {
            case'jpg':
                $src_img = imagecreatefromjpeg($imgpath);
                break;
            case'png':
                $src_img = imagecreatefrompng($imgpath);
                break;
        }
        $wh = getimagesize($imgpath);
        $w = $wh[0];
        $h = $wh[1];
        $w = min([$w, $h]);
        $h = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $w / 2; //圆半径
        $y_x = $r; //圆心X坐标
        $y_y = $r; //圆心Y坐标
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        return $img;
    }

    /**
     * 获取公有愿望信息
     */
    public function getPublicWishList() {
        $uid = (int) $this->request->post('uid', 100001);
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            return $this->error('用户不存在。。');
        }
        $aLastWish = Db::name('wish')->where(['uid' => $uid, 'status' => 1])->find();
        if (!$aLastWish) {
            return $this->error('正在进行的愿望不存在请先创建。。');
        }
        $wishId = $aLastWish['id'];
        $aData['u_name'] = $aUser['username'];
        $aData['head_img'] = $aUser['head_img_url'];
        $aData['login_date'] = date('Y-m-d', $aLastWish['create_time']);
        $aData = array_merge($aData, $this->_getWishBaseInfo($wishId));
        //祈福磅单
        $aQifuList = Db::name('wish_blessing')->where(['wish_id' => $wishId])->field('sum(fudai_shus) as fudai_nums,id,uid')->group('uid')->order('fudai_nums desc')->select();
        foreach ($aQifuList as &$aFu) {
            $aUser = Db::name('users')->find($aFu['uid']);
            $aFu['u_name'] = $aUser['username'];
            $aFu['head_img'] = $aUser['head_img_url'];
        }
        $aData['u_list'] = $aQifuList;
        $aData['wish_id'] = $wishId;
        $aData['now_qifu_nums'] = Db::name('wish_blessing')->count();
        $aData['this_wish_qifu_nums'] = Db::name('wish_blessing')->where(['wish_id' => $wishId])->count();
        $this->success('获取成功 ：shengcun_nums 已经生存天数 qifu_nums 祈福数量 fudai_nums 福袋数量, now_qifu_nums 当前祈福人数 this_wish_qifu_nums 已有祈福人数', '', $aData);
    }

    
        /**
     * 获取公有愿望信息
     */
    public function getWishShareList() {
        $uid = (int) $this->request->post('uid', 100001);
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            return $this->error('用户不存在。。');
        }
        $aLastWish = Db::name('wish')->where(['uid' => $uid, 'status' => 1])->find();
        if (!$aLastWish) {
            return $this->error('正在进行的愿望不存在请先创建。。');
        }
        $wishId = $aLastWish['id'];
        $aData['u_name'] = $aUser['username'];
        $aData['head_img'] = $aUser['head_img_url'];
        //祈福磅单
        $aQifuList = Db::name('wish_blessing')->where(['wish_id' => $wishId, 'is_share' => 1])->field('sum(fudai_shus) as fudai_nums,id,uid')->group('uid')->order('fudai_nums desc')->select();
        foreach ($aQifuList as &$aFu) {
            $aUser = Db::name('users')->find($aFu['uid']);
            $aFu['u_name'] = $aUser['username'];
            $aFu['head_img'] = $aUser['head_img_url'];
        }
        $aData['u_list'] = $aQifuList;
        $aData['wish_id'] = $wishId;
        $aData['wish_title'] = $aLastWish['title'];
        $aData['total_qifu_nums'] = Db::name('wish_blessing')->where(['wish_id' => $wishId, 'is_share' => 1])->sum('fudai_shus');
        $aData['total_friend'] = Db::name('wish_blessing')->where(['wish_id' => $wishId, 'is_share' => 1])->group('uid')->count();
        $this->success('获取成功 ：wish_title 愿望标题 total_qifu_nums 当前分享进来的祈福数量 total_friend 已收集好友数量', '', $aData);
    }

    /**
     * 给他人愿望祈福
     * @return type
     */
    public function qifu() {
        //当前登录的用户id
        $uid = (int) $this->request->post('uid', 100001);
        $wishId = (int) $this->request->post('wish_id', 4);
        $is_share = (int) $this->request->post('is_share', 0);
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            return $this->error('用户不存在。。');
        }
        $aLastWish = Db::name('wish')->where(['id' => $wishId, 'status' => 1])->find();
        if (!$aLastWish) {
            return $this->error('正在进行的愿望不存在。。');
        }
        $todayIsQifu = Db::name('wish_blessing')->where(['wish_id' => $wishId, 'uid' => $uid, 'q_date' => date('Ymd')])->count();
        if ($todayIsQifu) {
            return $this->error('今天已经祈福过了，请明日再祈福');
        }
        $fudai_shus = mt_rand(1, 10);
        Db::name('wish_blessing')->insertGetId([
            'wish_id' => $wishId,
            'uid' => $uid,
            'wish_uid' => $aLastWish['uid'],
            'creat_time' => NOW_TIME,
            'q_date' => date('Ymd'),
            'fudai_shus' => $fudai_shus,
            'is_share' => $is_share
        ]);
        $this->success('祈福成功', '', [
            'fudaishu' => $fudai_shus,
        ]);
    }

    public function getOrderList() {
        $uid = (int) $this->request->post('uid', 100001);
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('page_size', 1000);
        $list = Db::name('wish_order_log')->where(['uid' => $uid])->order('create_time desc')->page($page, $pageSize)->select();
        if (!$list) {
            return $this->error('订单为空');
        }
        foreach ($list as &$val) {
            $val['date'] = date('Y-m-d H:i:s', $val['create_time']);
        }
        return $this->success('获取订单列表成功 type 等于 1 表示加钱 type 等于2 表示减钱', '', $list);
    }

    public function quitWish() {
        $uid = (int) $this->request->post('uid', 100001);
        $bingId = (int) $this->request->post('bind_id', 1);
        $aUser = Db::name('users')->find($uid);
        if (!$aUser) {
            return $this->error('用户不存在');
        }
        $aLastWish = Db::name('wish')->where(['uid' => $uid, 'status' => 1])->find();
        if (!$aLastWish) {
            return $this->error('没有可退出的愿望。。');
        }
        $aBind = Db::name('user_alipay_bank')->where(['uid' => $uid, 'id' => $bingId])->find();
        if (!$aBind) {
            return $this->error('请先绑定，银行卡或者支付不宝');
        }
        //开启事务处理
        Db::startTrans();
        try {
            Db::name('wish')->where(['id' => $aLastWish['id']])->update([
                'quit_time' => NOW_TIME,
                'status' => 3,
            ]);
            //统计所需要退回的钱
            $backMoney = Db::name('wish_order')->where(['wish_id' => $aLastWish['id']])->sum('money');
            if ($backMoney > 0) {
                Db::name('app_withdrawal')->insert([
                    'uid' => $uid,
                    'money' => $backMoney,
                    'status' => 0,
                    'date' => date('Ym'),
                    'create_time' => NOW_TIME,
                    'pay_account' => $aBind['account'],
                    'pay_name' => $aBind['true_name'],
                    'type' => $aBind['type'],
                    'bank_name' => $aBind['bank_name'] ? $aBind['bank_name'] : '',
                ]);
                Db::name('wish_order_log')->insertGetId([
                    'remark' => '愿望退出提现',
                    'create_time' => NOW_TIME,
                    'type' => 2,
                    'money' => $backMoney,
                    'uid' => $uid,
                ]);
            }
            Db::commit();
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('退出失败');
        }
        return $this->success('退出成功');
    }

    public function getBankList() {
        $aList = Db::name('bank')->order('sort asc,id asc')->select();
        $this->success('获取成功', '', $aList);
    }

    public function getBindAcountList() {
        $uid = (int) $this->request->post('uid', 100001);
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            return $this->error('用户不存在。。');
        }
        $aInfo = Db::name('user_alipay_bank')->where(['uid' => $uid])->select();
        $this->success('获取银行卡支付宝信息成功', '', $aInfo);
    }

    public function bindAcount() {
        $uid = (int) $this->request->post('uid', 100001);
        $account = $this->request->post('account', 'xxx@qq.com');
        $type = $this->request->post('type', 1);
        $true_name = $this->request->post('true_name', '尚公子');
        //开户行
        $bankId = (int) $this->request->post('bank_id', 0);
        $aUser = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUser) {
            return $this->error('用户不存在。。');
        }
        if ($type == 2 && !$bankId) {
            return $this->error('选择银行卡类型开户行必填');
        }
        $bankName = '';
        if ($type == 2) {
            $aBank = Db::name('bank')->find($bankId);
            $bankName = $aBank['name'];
        }
        $aInfo = Db::name('user_alipay_bank')->where(['uid' => $uid, 'type' => $type])->find();
        if (!$aInfo) {
            Db::name('user_alipay_bank')->insertGetId([
                'uid' => $uid,
                'account' => $account,
                'true_name' => $true_name,
                'type' => $type,
                'create_time' => NOW_TIME,
                'bank_id' => $bankId,
                'bank_name' => $bankName,
            ]);
        } else {
            Db::name('user_alipay_bank')->where(['id' => $aInfo['id']])->update([
                'account' => $account,
                'true_name' => $true_name,
                'update_time' => NOW_TIME,
                'bank_id' => $bankId,
                'bank_name' => $bankName,
            ]);
        }
        return $this->success('保存成功');
    }
    
    /**
     * 获取支付参数
     */
    public function getWxAppPaySign() {
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
        $aUserInfo = Db::name('user_open_binds')->where(['user_id' => $userId])->find();
        if (!$aUserInfo) {
            return $this->error('找不到用户');
        }
        // 3. 创建接口实例
        $wechat = new Pay(WxappConfig::getConfig());

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
    
    public function setUserPidInfo() {
        $uid = (int) $this->request->post('uid', 0);
        $pid = $this->request->post('pid', '');
        if (!$pid || !$uid) {
            $this->error('分享人id 和用户id必填');
        }
        $aUserInfo = Db::name('users')->where(['id' => $uid])->find();
        if (!$aUserInfo) {
            $this->error('用户不存在');
        }
        return $this->success('ok');
    }

}
