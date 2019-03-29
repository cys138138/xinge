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
        $fans = WechatService::webOauth(1);
        print_r($fans);       
    }
}
