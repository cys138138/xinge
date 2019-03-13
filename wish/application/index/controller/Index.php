<?php


namespace app\index\controller;

use think\Controller;

/**
 * 应用入口控制器
 */
class Index extends Controller
{

    public function index()
    {
        $this->redirect('/active/index/index.html?s_pid=4');
    }

}
