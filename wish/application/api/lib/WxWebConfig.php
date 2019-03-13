<?php
/**
 * api控制中心
 */
namespace app\api\lib;


class WxWebConfig {
    public static function getConfig(){
        return  [
            'token' => sysconf('wechat_token'),
            'appid' => sysconf('wechat_appid'),
            'appsecret' => sysconf('wechat_appsecret'),
            'encodingaeskey' => sysconf('wechat_encodingaeskey'),
            
            'mch_id'         => sysconf('wechat_mch_id'),
            'mch_key'        => sysconf('wechat_partnerkey'),
            'ssl_cer'        => sysconf('wechat_cert_cert'),
            'ssl_key'        => sysconf('wechat_cert_key'),
            'cachepath'      => env('cache_path') . 'wechat' . DIRECTORY_SEPARATOR,
        ];
    }
}
