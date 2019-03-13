<?php

use service\DataService;
use service\NodeService;
use think\Db;

/**
 * 打印输出数据到文件
 * @param mixed $data 输出的数据
 * @param bool $force 强制替换
 * @param string|null $file
 */
function p($data, $force = false, $file = null)
{
    is_null($file) && $file = env('runtime_path') . date('Ymd') . '.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
    $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
}

/**
 * RBAC节点权限验证
 * @param string $node
 * @return bool
 */
function auth($node)
{
    return NodeService::checkAuthNode($node);
}

/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是null为获取值，否则为更新
 * @return string|bool
 * @throws \think\Exception
 * @throws \think\exception\PDOException
 */
function sysconf($name, $value = null)
{
    static $config = [];
    if ($value !== null) {
        list($config, $data) = [[], ['name' => $name, 'value' => $value]];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        $config = Db::name('SystemConfig')->column('name,value');
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 日期格式标准输出
 * @param string $datetime 输入日期
 * @param string $format 输出格式
 * @return false|string
 */
function format_datetime($datetime, $format = 'Y年m月d日 H:i:s')
{
    return is_numeric($datetime) ? date($format, $datetime) : date($format, strtotime($datetime));
}

/**
 * UTF8字符串加密
 * @param string $string
 * @return string
 */
function encode($string)
{
    list($chars, $length) = ['', strlen($string = iconv('utf-8', 'gbk', $string))];
    for ($i = 0; $i < $length; $i++) {
        $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
    }
    return $chars;
}

/**
 * UTF8字符串解密
 * @param string $string
 * @return string
 */
function decode($string)
{
    $chars = '';
    foreach (str_split($string, 2) as $char) {
        $chars .= chr(intval(base_convert($char, 36, 10)));
    }
    return iconv('gbk', 'utf-8', $chars);
}

/**
 * 下载远程文件到本地
 * @param string $url 远程图片地址
 * @return string
 */
function local_image($url)
{
    return \service\FileService::download($url)['url'];
}

/**
 * 4四舍五入价格
 * @param type $price
 * @return type
 */
function price($price) {
    return substr(sprintf("%.3f", $price), 0, -1);
}

function hideStar($str) { 
    if(!$str){
        return '--';
    }
    //用户名、邮箱、手机账号中间字符串以*隐藏
  if (strpos($str, '@')) {
    $email_array = explode("@", $str);
    $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
    $count = 0;
    $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
    $rs = $prevfix . $str;
  } else {
    $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
    if (preg_match($pattern, $str)) {
      $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
    } else {
      $rs = substr($str, 0, 3) . "***" . substr($str, -1);
    }
  }
  return $rs;
}

function getUserInfo($feild){
    $aUser = session('ac_users');
    if (!$aUser) {
        return 0;
    }
    return isset($aUser[$feild]) ? $aUser[$feild] : 0;
}

function getUserTeamTag() {
    $aUser = session('ac_users');
    if (!$aUser) {
        return 0;
    }

    $aMySelfTeam = Db::name("active_team")->where(['uid' => $aUser['id']])->find();
    if (!$aMySelfTeam) {
        return 0;
    }
    return $aUser['id'] . '_' . $aMySelfTeam['id'];
}

function haveLevelTeam() {
    $aUser = session('ac_users');
    if (!$aUser) {
        return 0;
    }

    $aMySelfTeam = Db::name("active_team")->where(['uid' => $aUser['id'],'pid'=>0])->find();
    if (!$aMySelfTeam) {
        return 0;
    }
    return 1;
}
