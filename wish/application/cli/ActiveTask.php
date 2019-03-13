<?php

namespace app\cli;

use app\active\service\Config;
use app\active\service\UserService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;

class ActiveTask extends Command {

    /**
     * 配置一个买卖市场定时任务指令
     */
    protected function configure() {
        $this->setName('active_task')
                ->setDefinition([
                    //Option 和 Argument都可以有多个, Argument的读取和定义的顺序有关.请注意
                    new Option('option', 'o', Option::VALUE_OPTIONAL, "命令option选项"), //使用方式  php think hello  --option test 或 -o test
                    new Argument('action_type', Argument::OPTIONAL, "action_type参数"),
                ])//使用方式    php think hello  test1 (将输入的第一个参数test1赋值给定义的第一个Argument参数test)
                ->setDescription('active auto settlement'); //每天都跑的任务
    }

    /**
     * 执行方法
     * 这个任务整10分钟一次
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output) {
        $action = $input->getArgument('action_type');
        if (!$action) {
            $output->writeln("Argument is not defined");
            return 0;
        }
        //十分钟任务
        if ($action == 'tenTask') {
            $this->tenTask($input, $output);
            return 0;
        }
        //每晚9点执行的任务
        if ($action == 'pm9Task') {
            $this->pm9Task($input, $output);
            return 0;
        }

        return 0;
    }

    /**
     * 晚上九点查看是否需要产生金猪奖和结束活动
     * @param Input $input
     * @param Output $output
     */
    public function pm9Task(Input $input, Output $output) {
        $aActiveList = Db::name('active_batch')->where(['status' => 1])->select();
        foreach ($aActiveList as $aItem) {
            //判断最后一小时是否有消费，如果没有就产生金猪奖，并且结束当前活动
            $startTime = strtotime(date('Y-m-d 20:00:00'));
            $time = time();
            $aActiveBuyLogList = Db::name('active_user_buy_log')->where(['acb_id' => $aItem['id']])->whereBetween('buy_time', [$startTime, $time])->select();
            //如果有消费就不用往下了
            if (!empty($aActiveBuyLogList)) {
                $this->todayPushTop3($aItem['id']);
                continue;
            }
            $todayMoney = $this->getTodayAllMoneyByAbcId($aItem['id']);
            if ($todayMoney < 0.01) {
                static::log("当日累计金额可分佣小于0.01元，今天没有金猪奖");
                return false;
            }
            //平台总消费
            $totalAbcMoney = $this->getAllMoneyByAbcId($aItem['id']);
            $jzMoney = Config::$JZ_PRECENT * $totalAbcMoney;
            //计算今天的金猪奖得主
            $aUserBuyLog = Db::name('active_user_buy_log')->where(['acb_id' => $aItem['id']])->order('buy_time desc')->find();
            //发放奖励
            Db::name('active_user_reward')->insertGetId([
                'create_time' => $time,
                'uid' => $aUserBuyLog['uid'],
                'acb_id' => $aUserBuyLog['acb_id'],
                'is_withdrawal' => 1,
                'type' => 2,
                'reason' => "金猪得主",
                'acm_id' => 0,
                'money' => $jzMoney,
            ]);
            UserService::addUserMoney($aUserBuyLog['uid'], $jzMoney, 2, '金猪得主');
            //更新位置表
            Db::name('active_user_buy_log')->where(['id' => $aUserBuyLog['id']])->update([
                'get_money' => $aUserBuyLog['get_money'] + $jzMoney,
                'update_time' => $time,
            ]);
            static::log("今天开出了金猪奖了 得主用户id是:" . $aUserBuyLog['uid']);
            //发放倒数2到99奖励
            $this->t2And99($aItem['id']);
            $this->todayPushTop3($aItem['id']);
            //结束活动
            Db::name('active_batch')->where(['id' => $aUserBuyLog['acb_id']])->update([
                'end_time' => $time,
                'status' => 2,
                'win_uid' => $aUserBuyLog['uid'],
            ]);
            //创建第二天的活动
            Db::name('active_batch')->insert([
                'title' => date('Y-m-d-H-i-s') . '创建的活动',
                'start_time' => $time,
                'status' => 1,
            ]);
        }
    }

    /**
     * 倒数2-99
     */
    public function t2And99($abcId) {
        static::log("倒数2到99名发放奖励任务执行");
        //倒数2 到 99
        //今天
//        $startTime = strtotime(date('Y-m-d 00:00:00'));
        $aUserBuyLogList = Db::name('active_user_buy_log')->where(['acb_id' => $abcId])->order('buy_time desc')->limit(100)->select();
        //踢掉最后一位
        $aOkList = array_slice($aUserBuyLogList, 1);
        $i = 2;
        $todayMoney = $this->getTodayAllMoneyByAbcId($abcId);
        if ($todayMoney < 0.01) {
            static::log("倒数任务：当日累计金额可分佣小于0.01元，今天不分了");
            return false;
        }
        //平台总消费
        $totalAbcMoney = $this->getAllMoneyByAbcId($abcId);
        $totalMoney = Config::$DAO_2_99_PRECENT * $totalAbcMoney;
        $oneUserPrice = $totalMoney / count($aOkList);
        if($oneUserPrice < 0.01){
            static::log("2-99名 均分小于0.01");
            return false;
        }
        
        foreach ($aOkList as $aUserBuyLog) {
            $this->sendReward($aUserBuyLog['uid'], $aUserBuyLog['acb_id'], $oneUserPrice, 4, $aUserBuyLog['id'], $aUserBuyLog['get_money'], date('Y-m-d') . "倒数" . $i . '名奖励');
            $i++;
        }
    }

    /**
     * 奖励发放
     * @param type $userId
     * @param type $abcId
     * @param type $money
     * @param type $type
     * @param type $buyLogId
     * @param type $oldMoney
     * @param type $remark
     */
    public function sendReward($userId, $abcId, $money, $type, $buyLogId, $oldMoney, $remark) {
        $time = time();
        Db::name('active_user_reward')->insertGetId([
            'create_time' => $time,
            'uid' => $userId,
            'acb_id' => $abcId,
            'is_withdrawal' => 1,
            'type' => $type,
            'reason' => $remark,
            'acm_id' => 0,
            'money' => $money,
        ]);

        UserService::addUserMoney($userId, $money, $type, $remark);
        if ($buyLogId) {
            //更新位置表
            Db::name('active_user_buy_log')->where(['id' => $buyLogId])->update([
                'get_money' => $oldMoney + $money,
                'update_time' => $time,
            ]);
        }
    }

    /**
     * 今天推荐人数最多前3名发放奖励
     */
    public function todayPushTop3($abcId) {
        static::log("今天推荐人数前3的任务开始执行了");
        $todayMoney = $this->getTodayAllMoneyByAbcId($abcId);
        if ($todayMoney < 0.01) {
            static::log("推荐前3任务：当日累计金额可分佣小于0.01元，今天不分了");
            return false;
        }
        $aUserList = $this->_getTodayData();
        if ($aUserList) {
            //第一名
            if (isset($aUserList[0])) {
                $money = Config::$PUSH_ONE * $todayMoney;
                $this->sendReward($aUserList[0]['uid'], $abcId, $money, 5, 0, 0, date("Y-m-d") . '推荐消费排行第一名奖励');
            }
            //第二名
            if (isset($aUserList[1])) {
                $money2 = Config::$PUSH_TWO * $todayMoney;
                $this->sendReward($aUserList[1]['uid'], $abcId, $money2, 5, 0, 0, date("Y-m-d") . '推荐消费排行第二名奖励');
            }
            //第三名
            if (isset($aUserList[2])) {
                $money3 = Config::$PUSH_THREE * $todayMoney;
                $this->sendReward($aUserList[2]['uid'], $abcId, $money3, 5, 0, 0, date("Y-m-d") . '推荐消费排行第三名奖励');
            }
        }
    }

    /**
     * 获取消费排行前3发奖励
     * @return type
     */
    private function _getTodayData() {
        //获取当天前几名
        $startTime = strtotime(date('Y-m-d 00:00:00'));
        //获取今天所有参与的用户
        $aOneUserData = Db::name("active_user_buy_log")->where("buy_time >=" . $startTime)->select();
        //找出一二级用户
        $aDiffUser = [];
        foreach ($aOneUserData as $aItem) {
            if (!in_array($aItem['uid'], $aDiffUser)) {
                $aDiffUser[] = $aItem['uid'];
            }
            if ($aItem['pid_l1'] > 0 && !in_array($aItem['pid_l1'], $aDiffUser)) {
                $aDiffUser[] = $aItem['pid_l1'];
            }
        }
        if (!$aDiffUser) {
            return [];
        }
        $aFinishData = [];
        foreach ($aDiffUser as $uid) {
            $money = Db::name("active_user_buy_log")
                    ->where("buy_time >=" . $startTime)
                    ->where("uid =" . $uid . ' or pid_l1 = ' . $uid)
                    ->sum('money');


            $today_user_nums = Db::name("users")
                    ->where("createtime >=" . $startTime)
                    ->where(['pid' => $uid])
                    ->count();
            $aUserInfo = Db::name("users")
                    ->field('mobile as uname')
                    ->where(['id' => $uid])
                    ->find();
            $user_name = $aUserInfo['uname'];

            $aFinishData[] = [
                'uid' => $uid,
                'today_total_money' => (int) $money,
                'today_user_nums' => $today_user_nums,
                'user_name' => $user_name,
            ];
        }
        $sortFeild1 = array_column($aFinishData, 'today_total_money');
        $sortFeild2 = array_column($aFinishData, 'today_user_nums');
        array_multisort($sortFeild1, SORT_DESC, $sortFeild2, SORT_DESC, $aFinishData);
        //截断
        return array_slice($aFinishData, 0, 3);
    }
    
    
    /**
     * 获取今天的平台消费总金额
     * @param type $id
     * @return type
     */
    public function getTodayAllMoneyByAbcId($id) {
        $startTime = strtotime(date('Y-m-d 00:00:00'));
        return Db::name('active_user_buy_log')->where(['acb_id' => $id])->where('buy_time >=' . $startTime)->sum('money');
    }
    
    /**
     * 获取平台消费总金额
     * @param type $id
     * @return type
     */
    public function getAllMoneyByAbcId($id) {
        return Db::name('active_user_buy_log')->where(['acb_id' => $id])->sum('money');
    }

    /**
     * 十分钟一次奖励
     * @return type
     */
    public function tenTask(Input $input, Output $output) {
        //获取每天的买卖市场信息，第二条早上清理
        $aActiveList = Db::name('active_batch')->where(['status' => 1])->select();
        $totalUsers = 0;
        $aUserIds = [];
        $outList = [];
        foreach ($aActiveList as $aItem) {
            //看最后一次的奖励时间
            $aActiveMoney = Db::name('active_money_log')->where(['acb_id' => $aItem['id']])->order('create_time desc')->find();
            //如果不存在已经奖励的记录这个时间从0取出全部
            $startTime = 0;
            //找出上一次奖励时间
            if ($aActiveMoney) {
                $startTime = $aActiveMoney['create_time'];
            }
            $time = time();
            //上一次分账的时间到现在的消费分给10分钟前的用户
            $aActiveBuyLogList = Db::name('active_user_buy_log')->where(['acb_id' => $aItem['id'], 'status' => 1])->whereBetween('buy_time', [$startTime, $time])->select();
            if (empty($aActiveBuyLogList)) {
                static::log("没有消费记录，没得分");
                $output->writeln("定时任务执行了，但是木有人");
                return;
            }
            //本次总共消费是
            $aAllMoney = array_column($aActiveBuyLogList, 'money');
            $totalMoney = array_sum($aAllMoney);
            $totalCommission = $totalMoney * Config::$COMMISSION_PRCENT;

            //第一次谁都不分
            if (empty($aActiveMoney)) {
                //保存截止时间点
                Db::name('active_money_log')->insertGetId([
                    'create_time' => $time,
                    'acb_id' => $aItem['id'],
                    'one_money' => 0.0,
                    'user_total' => 0.0,
                    'total_money' => $totalCommission,
                ]);
                static::log("任务开始前10分钟没人可以分");
                return;
            }
            //本次可以分佣的前10分钟人
            $tenTime = $time - 600;
            //获取所有凳子位置
            $aActivePositionList = Db::name('active_user_buy_log')->where(['acb_id' => $aItem['id'], 'status' => 1])->where("buy_time <=" . $tenTime)->select();

            //本轮10分钟总共有多少人可以分
            $allUserNums = count($aActivePositionList);
            //每个人能分多少
            $oneUserPrice = $totalCommission / $allUserNums;
            //丢掉小数点
            $oneUserPrice = substr(sprintf("%.3f", $oneUserPrice), 0, -1);
            //计算是否达到0.01 临界值
            if ($oneUserPrice < 0.01) {
                static::log("消费总金额平方小于0.01元跳过本次");
                $output->writeln("定时任务执行了，但是木有人");
                return;
            }

            $allOutMoney = 0.0;
            //这里一个位置 份购600 就要出局
            foreach ($aActivePositionList as $aUserBuyLog) {
                $canGetMoney = 600 - $aUserBuyLog['get_money'];
                //不能分了 出局
                if ($canGetMoney < 0) {
                    Db::name('active_user_buy_log')->where(['id' => $aUserBuyLog['id']])->update([
                        'out_time' => $time,
                        'status' => 2,
                    ]);
                    //记录出局的
                    $outList[] = $aUserBuyLog['id'];
                    $allOutMoney += $oneUserPrice;
                    continue;
                }
                //如果剩余能分的小于本次可分的 分完出局
                if ($canGetMoney < $oneUserPrice) {
                    //只能分部分
                    Db::name('active_user_reward')->insertGetId([
                        'create_time' => $time,
                        'uid' => $aUserBuyLog['uid'],
                        'acb_id' => $aUserBuyLog['acb_id'],
                        'is_withdrawal' => 0,
                        'type' => 0,
                        'reason' => "活动奖励",
                        'acm_id' => isset($aActiveMoney['id']) ? $aActiveMoney['id'] : 0,
                        'money' => $canGetMoney,
                    ]);
                    UserService::addUserMoney($aUserBuyLog['uid'], $canGetMoney, 0, '活动奖励');
                    Db::name('active_user_buy_log')->where(['id' => $aUserBuyLog['id']])->update([
                        'out_time' => $time,
                        'status' => 2,
                    ]);
                    //更新位置表
                    Db::name('active_user_buy_log')->where(['id' => $aUserBuyLog['id']])->update([
                        'get_money' => $aUserBuyLog['get_money'] + $canGetMoney,
                        'update_time' => $time,
                    ]);
                    //记录出局的
                    $outList[] = $aUserBuyLog['id'];
                    //出局剩余金额
                    $allOutMoney += ($oneUserPrice - $canGetMoney);
                    continue;
                }

                Db::name('active_user_reward')->insertGetId([
                    'create_time' => $time,
                    'uid' => $aUserBuyLog['uid'],
                    'acb_id' => $aUserBuyLog['acb_id'],
                    'is_withdrawal' => 1,
                    'type' => 0,
                    'reason' => "时间段奖励",
                    'acm_id' => isset($aActiveMoney['id']) ? $aActiveMoney['id'] : 0,
                    'money' => $oneUserPrice,
                ]);
                UserService::addUserMoney($aUserBuyLog['uid'], $oneUserPrice, 0, '活动奖励');
                //更新位置表
                Db::name('active_user_buy_log')->where(['id' => $aUserBuyLog['id']])->update([
                    'get_money' => $aUserBuyLog['get_money'] + $oneUserPrice,
                    'update_time' => $time,
                ]);
                //记录每个批次的分佣人（如果重复表示这个人参加了多个位置）
                $aUserIds[$aItem['id']][] = $aUserBuyLog['uid'];
            }
            //保存截止时间点
            Db::name('active_money_log')->insertGetId([
                'create_time' => $time,
                'acb_id' => $aItem['id'],
                'one_money' => $oneUserPrice,
                'user_total' => $allUserNums,
                'total_money' => $totalCommission,
                'out_total_money' => $allOutMoney,
            ]);
            //总共有多少人参与分佣这次
            $totalUsers += $allUserNums;
        }
        static::log('总共有' . $totalUsers . '人次参与,列表：' . json_encode($aUserIds) . '出局的 位置列表' . json_encode($outList));
        $output->writeln("定时任务执行了");
    }

    public static function log($reson) {
        $data = [
            'ip' => '127.0.0.1',
            'node' => '/active_task',
            'action' => '活动结算任务执行了',
            'content' => '定时任务在' . date('Y-m-d H:i:s') . '执行 备注' . $reson,
            'username' => 'system_active_task',
        ];
        Db::name('SystemLog')->insert($data);
    }

}
