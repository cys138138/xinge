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

        $feedback = Db::name('app_feedback')->where(['uid' => $uid])->find();
        //5分钟不允许不断提交
        if ($feedback && (time() - $feedback['create_time']) < 120) {
            return $this->error('操作太频繁');
        }
        $urlList = [];
        if ($bs64 && !empty($bs64)) {
            foreach ($bs64 as $content) {
                $aInfo = \service\FileService::local(md5(time), $content);
                $urlList[] = $aInfo['url'];
            }
        }
        Db::name('app_feedback')->insert([
            'create_time' => time(),
            'content' => $content,
            'uid' => $uid,
            'attache' => join(',', $urlList),
        ]);
        return $this->success('反馈成功');
    }

}
