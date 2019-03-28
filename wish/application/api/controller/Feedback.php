<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use think\Db;

/**
 * 反馈
 */
class Feedback extends BaseController {

    /**
     * 获取关注列表
     */
    public function addFeedback() {
        $uid = (int) $this->request->post('uid', 0);
        $content = $this->request->post('content', '');
        $bs64 = $this->request->post('bs_64_list', '');
        if(!$content){
            return $this->error('反馈内容为空');
        }

        $feedback = Db::name('app_feedback')->where(['uid' => $uid])->find();
        //5分钟不允许不断提交
        if ($feedback && (time() - $feedback['create_time']) < 120) {
            return $this->error('操作太频繁');
        }
        Db::name('app_feedback')->insert([
            'create_time' => time(),
            'content' => $content,
            'uid' => $uid,
            'attache' => $bs64,
        ]);
        return $this->success('反馈成功');
    }

}
