<?php

/**
 * api控制中心
 */
namespace app\api\lib;

use app\lib\CpsPdd;
use app\lib\Time;
use think\Container;
use think\Controller;

/**
 * 应用入口控制器
 */
class BaseController extends Controller
{
    protected $oPdd = null;
    public function initialize(){
		header('Access-Control-Allow-Origin:*');		
        if($this->oPdd == null){
            $this->oPdd = new CpsPdd();
        }
    }


    protected function getResponseType() {
        $config = Container::get('config');
        return $config->get('default_ajax_return');
    }
    
    
    protected function dateFormater(&$aData, $feild = 'create_time') {
        //一维数组
        if (count($aData) == count($aData, 1)) {
            if (isset($aData[$feild])) {
                $aData[$feild] = Time::friendlyDate($aData[$feild],'one_month');
            }
        } else {
            foreach ($aData as &$aItem) {
                if (isset($aItem[$feild])) {
                    $aItem[$feild] = Time::friendlyDate($aItem[$feild],'one_month');
                }
            }
        }
    }

}
