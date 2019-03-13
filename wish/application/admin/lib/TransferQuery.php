<?php

namespace app\admin\lib;

use WeChat\Contracts\BasicAliPay;

/**
 * 支付宝转账到账户
 * Class Transfer
 * @package AliPay
 */
class TransferQuery extends BasicAliPay
{

    /**
     * Transfer constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
       $this->options->set('method', 'alipay.fund.trans.toaccount.transfer');
    }

    /**
     * 创建数据操作
     * @param array $options
     * @return mixed
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function apply($options)
    {
        return $this->getResult($options);
    }
    
    public function query($out_trade_no = '')
    {
        $this->options->set('method', 'alipay.fund.trans.order.query');
        return $this->getResult(['out_biz_no' => $out_trade_no]);
    }
}