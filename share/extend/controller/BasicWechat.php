<?php


namespace controller;

use service\WechatService;
use think\Controller;

/**
 * 微信基础控制器
 * Class BasicWechat
 * @package controller
 */
class BasicWechat extends Controller
{

    /**
     * 当前粉丝用户OPENID
     * @var string
     */
    protected $openid;

    /**
     * 获取粉丝用户OPENID
     * @return bool|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function getOpenid()
    {
        return WechatService::webOauth(0)['openid'];
    }

    /**
     * 获取微信粉丝信息
     * @return bool|array
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function getFansinfo()
    {
        return WechatService::webOauth(1)['fansinfo'];
    }

}
