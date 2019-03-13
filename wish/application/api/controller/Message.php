<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use think\Db;

/**
 * 基本配置
 */
class Message extends BaseController {

    /**
     * 获取我的站内信信息
     */
    public function getUserMessageList() {
        $userId = (int) $this->request->get('uid', 0);
        $list = Db::name('app_message')->where(['uid' => $userId])->order('status asc,create_time desc')->select();
        if (!$list) {
            $this->success('暂无数据', null, []);
        }
        $this->dateFormater($list);
        return $this->success('获取成功', null, $list);
    }
    
    /**
     * 检测是否有未读消息
     */
    public function checkNoRead() {
        $userId = (int) $this->request->get('uid', 0);
        $aMessage = Db::name('app_message')->where(['uid' => $userId, 'status' => 1])->find();
        if (!$aMessage) {
            $this->success('暂无数据', null, ['is_no_read' => 0]);
        }
        return $this->success('获取成功', null, ['is_no_read' => 1]);
    }

    
    /**
     * 获取详情设置已读
     */
    public function getUserMessageDetail() {
        $userId = (int) $this->request->get('uid', 0);
        $messageId = (int) $this->request->get('message_id', 0);
        $aMassge = Db::name('app_message')->where(['uid' => $userId, 'id' => $messageId])->find();
        if (!$aMassge) {
            $this->error('找不到未读消息，已经被删除');
        }
        if ($aMassge['status'] == 1) {
            //设置已读
            Db::name('app_message')->where(['uid' => $userId, 'id' => $messageId])->update([
                'status' => 2,
                'read_time' => NOW_TIME,
            ]);
        }
        return $this->success('获取成功', null, $aMassge);
    }

}
