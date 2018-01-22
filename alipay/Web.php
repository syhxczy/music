<?php
require_once dirname(dirname(__FILE__)).'/Alipay.php';
class Web extends Alipay
{

    public function pay($order=[])
    {
        parent::pay($order);
        return $this->buildPayHtml();
    }

    protected function getMethod()
    {
        return 'alipay.trade.page.pay';
    }

    protected function getProductCode()
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }
}
