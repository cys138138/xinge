<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use think\Db;

/**
 * 基本配置
 */
class Site extends BaseController {

    /**
     * banner
     */
    public function getBannerList() {
        //如果类型(type) 为分类(type=1) content 为 分类opt_id 如果类型为 单品(type=2) 内容商品id
        $list = Db::name('app_banner')->where(['status' => 1])->order('sort asc,id asc')->select();
        return $this->success('获取成功1', null, $list);
    }

    /**
     * 明星分类
     */
    public function getCategoryList() {
        $list = Db::name('art_category')->where(['status' => 1])->order('sort asc,id asc')->select();
        $this->success('获取成功', null, $list);
    }

    /**
     * 获取平台手续费
     */
    public function getPlatformCommission() {
        $oncePrice = (double) $this->request->get('one_price', 0.00);
        $nums = (int) $this->request->get('nums', 10);
        $totalMoney = $oncePrice * $nums;
        $commissionMoney = StarService::getPlateformCommission($totalMoney);
        $aResult = [
            'total_money' => $totalMoney,
            'commission_money' => $commissionMoney,
            'all_total_money' => $totalMoney + $commissionMoney,
        ];
        $this->success('获取成功', null, $aResult);
    }

    /**
     * 协议和帮助中心
     */
    public function getHelperList() {
        $type = (int) $this->request->get('type', 0);
        $list = Db::name('app_helper')->where(['status' => 1, 'cid' => $type])->order('sort asc,id asc')->select();       
        if (!$list) {
            $list = [];
        }
        $this->success('获取成功', null, $list);
    }

    /**
     * 协议和帮助中心
     */
    public function getHelperDetail() {
        $id = (int) $this->request->get('id', 0);
        if (!$id) {
            $this->error('缺少id');
        }
        $aDetail = Db::name('app_helper')->where(['status' => 1, 'id' => $id])->find();
        if (!$aDetail) {
            $this->error('文档不存在');
        }
        if (!$aDetail) {
            $aDetail = [];
        }
        $aDetail['content'] = htmlspecialchars_decode($aDetail['content']);
        $this->success('获取详细成功', null, $aDetail);
    }
    
    public function download(){
        $filename = $this->request->get('url', '','urldecode');
        header('Content-type: image/jpg');
        exit(file_get_contents($filename));
    }

}
