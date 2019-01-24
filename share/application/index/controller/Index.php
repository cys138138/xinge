<?php


namespace app\index\controller;

use service\WechatService;
use think\Controller;
use think\Db;

/**
 * 应用入口控制器
 */
class Index extends Controller
{

    public function index()
    {
        $id = $this->request->get('ss_id',0);
        $info = Db::name('xin_share_task')->where(['id'=>$id])->find();
        if(!$info){
            $this->error("非法访问");
        }        
        return $this->fetch('share',['data'=>$info]);
    }
    
    public function share() {
        $id = $this->request->get('ss_id', 0);
        $info = Db::name('xin_share_task')->where(['id' => $id])->find();
        if (!$info) {
            $this->error("非法访问");
        }
        Db::name('xin_share_pv_uv')->insertGetId([
            'ip' => $this->request->ip(),
            'create_time' => time(),
            't_id' => $id
        ]);
        $this->redirect($info['link'], [], 302);
    }

    public function getWxSign() {
        $url = $this->request->post('url', url('/index/index/index', null, true, true));
        $data = WechatService::webJsSDK($url);
        $this->success('ok', null, $data);
    }

}
