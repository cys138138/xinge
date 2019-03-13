<?php

namespace app\api\controller;

use app\api\lib\BaseController;
use app\api\service\StarService;
use think\Db;

/**
 * 艺人管理
 */
class Star extends BaseController {

    /**
     * 获取分类列表
     * @return type
     */
    public function getCategoryList() {
        $aWhere = ['status' => 1];
        $list = Db::name('art_category')->where($aWhere)->field('id,name as title,icon,sort')->select();
        if (!$list) {
            $this->success('暂无数据', null, []);
        }
        return $this->success('获取成功', null, $list);
    }

    /**
     * 获取文章
     */
    public function getStarList() {
        $page = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('page_size', 10);
        $cid = (int) $this->request->get('cid', 0);
        $order = (int) $this->request->get('order', 0);
        //获取顶部置顶那个
        $isPush = (int) $this->request->get('is_push', 0);
        //分类
        $aWhere = ['status' => 1, 'is_deleted' => 0];
        if ($cid) {
            $aWhere['cate_id'] = $cid;
        }

        $xOrder = 'sort desc,update_time desc';
        if ($order) {
            $xOrder = $order;
        }
        if ($isPush) {
            $aWhere['is_push'] = 1;
        }
        $list = Db::name('art_star')->where($aWhere)->order($xOrder)->page($page, $pageSize)->select();
        if (!$list) {
            $this->success('暂无数据', null, []);
        }
        //拆分数据
        foreach ($list as &$aItem) {
            $aItem['price'] = $aItem['purchase_price'];
            $aItem['price_percent'] = '0.00';
            //入市之后价格动态计算
            if ($aItem['type'] == 2) {                
                $aItem['price'] = StarService::getStarPrice($aItem['id']);  
                $aItem['price_percent'] = StarService::getZD($aItem['id']);                
            }
            list($minPrice, $maxPrice) = StarService::getMaxMinPrice($aItem['price']);
            $aItem['min_price'] = $minPrice;
            $aItem['max_price'] = $maxPrice;
        }
        return $this->success('获取成功', null, $list);
    }

    /**
     * 获取艺人详情接口
     * @return type
     */
    public function getStarDetail() {
        $starId = (int) $this->request->get('id', 0);
        $aWhere = ['status' => 1, 'is_deleted' => 0, 'id' => $starId];
        $aDetail = Db::name('art_star')->where($aWhere)->find();
        if (!$aDetail) {
            $this->error('找不到该艺人。。');
        }
        $aDetail['price'] = $aDetail['purchase_price'];
        $aDetail['price_percent'] = '0.00';
        if ($aDetail['type'] == 2) {
            $aDetail['price'] = StarService::getStarPrice($aDetail['id']);  
            $aDetail['price_percent'] = StarService::getZD($starId);
        }
        list($minPrice, $maxPrice) = StarService::getMaxMinPrice($aDetail['price']);
        $aDetail['min_price'] = $minPrice;
        $aDetail['max_price'] = $maxPrice;
        $aDetail['intro_img'] = $aDetail['intro_img'] != '' ? explode('|', $aDetail['intro_img']) : [];
        $aDetail['experience'] = htmlspecialchars_decode($aDetail['experience']);
        $aDetail['intro'] = htmlspecialchars_decode($aDetail['intro']);

        $aAttrList = Db::name('art_star_attr')->where(['star_id' => $aDetail['id'], 'status' => 1, 'is_deleted' => 0])->order('sort desc,update_time desc')->select();
        if (!$aAttrList) {
            $aAttrList = [];
        }
        $aDetail['attr_list'] = $aAttrList;
        $aDetail['fans_nums'] = Db::name('user_follow_star')->where(['star_id'=>$starId])->count();
        $aDetail['saler_nums'] = StarService::getSalesNums($starId);
        return $this->success('获取艺人信息成功', null, $aDetail);
    }
    
    /**
     * 获取某个明星交易报表
     * @return type
     */
    public function getReportData() {
        $starId = (int) $this->request->get('star_id', 0);
        $list = Db::name('art_star_market')->where(['star_id' => $starId, 'status' => 1, 'is_success' => 1])->field('price,buy_time,success_nums')->order('buy_time asc')->select();
        if (!$list) {
            $list = [];
        }
        $aDataList = array_map(function($item) {
            return (double) $item['price'];
        }, $list);

        $aTimeList = array_map(function($item) {
            return date('m-d H:i', $item['buy_time']);
        }, $list);

        $aNums = array_map(function($item) {
            return (int) $item['success_nums'];
        }, $list);

        $aAll = array_map(function($item) {
            return [
                'success_nums' => (int) $item['success_nums'],
                'buy_time' => date('m-d H:i', $item['buy_time']),
                'price' => (double) $item['price'],
            ];
        }, $list);

        $aData['one_price_list'] = $aDataList;
        $aData['date_list'] = $aTimeList;
        $aData['nums_list'] = $aNums;
        $aData['all_data_list'] = $aAll;
        return $this->success('获取信息报表成功', null, $aData);
    }

}
