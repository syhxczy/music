<?php

/**
* 
*/
class Alipay
{
    public $config;

    public $url = 'https://openapi.alipay.com/gateway.do';

    public $alipay_public_key = "";

    public $rsaPrivateKey = "";

    public function __construct()
    {
        $this->config = array(
            'app_id' => '',
            'format' => 'json', 
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'return_url'  => '',
            'notify_url'  => '',
            'method'      => '',
            'sign'        => '',
            'biz_content' => '',
        );
    }

    public function pay($order=[])
    {
        $order['product_code'] = $this->getProductCode();
        $this->config['method'] = $this->getMethod();
        $this->config['biz_content'] = json_encode($order);
        $this->config['sign'] = $this->getSign();
    }

    public function getSign() {
        $priKey = $this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($this->getSignContent($this->config), $sign, $res, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function getSignContent($data=[], $verify=false)
    {
        $stringToBeSigned = '';
        ksort($data);
        foreach ($data as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k.'='.$v.'&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k.'='.$v.'&';
            }
        }
        return trim($stringToBeSigned, '&');
    }

    public function rSignContent($params=[]) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    public function buildPayHtml()
    {
        $para_temp = $this->config;
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->url."?charset=utf-8' method='POST'>";
        while (list ($key, $val) = each ($para_temp)) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'","&apos;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }

    public function verify($data=[]) {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->alipay_public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $sign = $data['sign'];
        $data['sign_type'] = null;
        $data['sign'] = null;
        $toVerify = $this->rSignContent($data);
        $result = (bool)openssl_verify($toVerify, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        return $result;
    }

    public function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
} 

?>