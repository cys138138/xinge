<?php

namespace think;

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';
define('APP_PATH', __DIR__ . '/application/');
if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set( 'precision', 17 );
    ini_set( 'serialize_precision', -1 );
}
define('NOW_TIME',time());
// 执行应用并响应
Container::get('app', [__DIR__ . '/application/'])->run()->send();
