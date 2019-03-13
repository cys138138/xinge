<?php

namespace app\api\service;

use Exception;
use think\Db;

/**
 * 聚合短信类
 */
class JuheMessage {
    
    /**
     * 发送手机验证码
     * @param type $mobile
     * @param type $code
     */
    public static function sendCode($mobile, $code, $userId) {
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL

        $smsConf = array(
            'key' => '7ee9af2fb847d954906d502dc46456fe', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => '84456', //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => "#code#={$code}" //您设置的模板变量，根据实际情况修改
        );

        $content = static::juhecurl($sendUrl, $smsConf, 1); //请求发送短信

        if (!$content) {
            return false;
        }
        $result = json_decode($content, true);
        $error_code = $result['error_code'];
        if ($error_code != 0) {
            //状态非0，说明失败
            $msg = $result['reason'];
            return false;
        }
        Db::name('app_mobile_code')->insert([
            'code' => $code,
            'mobile' => $mobile,
            'uid' => $userId,
            'sid' => $result['result']['sid'],
            'create_time' => NOW_TIME,
        ]);
        return true;
    }

    /**
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $params [请求的参数]
     * @param  int $ipost [是否采用POST形式]
     * @return  string
     */
    public static function juhecurl($url, $params = false, $ispost = 0) {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }

}
