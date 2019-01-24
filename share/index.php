<?php

namespace think;

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';
define('APP_PATH', __DIR__ . '/application/');
// 执行应用并响应
Container::get('app', [__DIR__ . '/application/'])->run()->send();
