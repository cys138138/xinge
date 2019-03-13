<?php

namespace app\api\service;

use think\Db;

class MessageService {

    public static function send($userId, $title, $content, $is_system = 0) {
        return Db::name('app_message')->insertGetId([
                    'title' => $title,
                    'content' => $content,
                    'uid' => $userId,
                    'create_time' => NOW_TIME,
                    'is_system' => $is_system,
        ]);
    }

}
