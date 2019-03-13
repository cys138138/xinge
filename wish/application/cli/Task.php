<?php

namespace app\cli;

use app\api\service\MarketService;
use app\api\service\MessageService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;



class Task extends Command {

    /**
     * 配置一个买卖市场定时任务指令
     */
    protected function configure() {
        $this->setName('market_task')
                ->setDescription('market every day run task'); //每天都跑的任务
    }

    /**
     * 执行方法
     * 这个任务必须要在当天23点58分以后到24点之前执行完成
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output) {
        //获取每天的买卖市场信息，第二条早上清理
        $aMarkList = Db::name('art_star_market')->where(['status' => 0])
                ->whereBetween('create_time', static::getTodayTime())//只获取今天的
                ->select();
        $ids = [];
        foreach ($aMarkList as $aItem) {
            //卖
            if ($aItem['type'] == 1) {
                MarketService::cancelSale($aItem['uid'], $aItem['id']);
                $aStar = Db::name('art_star')->where(['id' => $aItem['star_id']])->find();
                if ($aStar) {
                    //站内信
                    MessageService::send($aItem['uid'], "转让【{$aStar['name']}】已过期", "你转让【{$aStar['name']}】{$aItem['time_length']}秒时间，已过期", 1);
                }
            } else {
                MarketService::cancelBuy($aItem['uid'], $aItem['id']);
                $aStar = Db::name('art_star')->where(['id' => $aItem['star_id']])->find();
                if ($aStar) {
                    //站内信
                    MessageService::send($aItem['uid'], "购买【{$aStar['name']}】已过期", "你发布购买【{$aStar['name']}】{$aItem['time_length']}秒时间，已过期", 1);
                }
            }
            $ids[] = $aItem['id'];
        }

        $data = [
            'ip' => '127.0.0.1',
            'node' => '/mark_task',
            'action' => '买卖市场定时任务执行了',
            'content' => '定时任务在' . date('Y-m-d H:i:s') . '执行了本次执行的market_ids列表' . json_encode($ids),
            'username' => 'system_task',
        ];
        Db::name('SystemLog')->insert($data);
        $output->writeln("定时任务执行了");
    }

    /**
     * 获取买卖市场任务清理时段
     * @return type
     */
    public static function getTodayTime() {
        /**
         * 今天之内
         */
        return [
            strtotime(date('Y-m-d 00:00:00')),
            strtotime(date('Y-m-d 00:00:00', strtotime('+1 day'))),
        ];
    }

}
