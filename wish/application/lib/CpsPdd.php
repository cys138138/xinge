<?php


namespace app\lib;

/**
 * 拼多多api通用类
 */
class CpsPdd {

    private $host = "http://gw-api.pinduoduo.com/api/router";
    private $clientId = 'a9606cf8d76944ffaa30f5885dc5b410';
    private $clientSecret = '87be6392335477bec82ff9481b1ee331568decbd';

    /**
     * 获取商品分类
     * see http://open.pinduoduo.com/#/apidocument/port?id=28
     * @param array $aParameter
     * @return type
     */
    public function getCateList($aParameter) {
        $aParameter['type'] = 'pdd.goods.cats.get';
        return $this->_doRequest($aParameter);
    }
    /**
     * 查询商品标签列表(获得拼多多商品标签列表)
     * see http://open.pinduoduo.com/#/apidocument/port?id=50
     * @param array $aParameter
     * @return type
     */
    public function getOptList($aParameter) {
        $aParameter['type'] = 'pdd.goods.opt.get';
        return $this->_doRequest($aParameter);
    }
    
    /**
     * 多多进宝商品查询
     * see http://open.pinduoduo.com/#/apidocument/port?id=28
     * @param array $aParameter
     * @return type
     */
    public function getGoodsList($aParameter) {
        $aParameter['type'] = 'pdd.ddk.goods.search';
        return $this->_doRequest($aParameter);
    }
    
    /**
     * 多多进宝商品查询
     * see http://open.pinduoduo.com/#/apidocument/port?id=28
     * @param array $aParameter
     * @return type
     */
    public function getGoodsDetail($aParameter) {
        $aParameter['type'] = 'pdd.ddk.goods.detail';
        return $this->_doRequest($aParameter);
    }
    
    /**
     * 创建多多进宝推广位(注意调用该方法之前应该先判断系统本身是否存在该用户的广告位)避免没必要的重复调用
     * @return type
     */
    public function generatePid($aParameter){
        $aParameter['type'] = 'pdd.ddk.goods.pid.generate';
        return $this->_doRequest($aParameter);
    }
    
    /**
     * 多多进宝推广链接生成
     * @return type
     */
    public function generateUrl($aParameter){
        $aParameter['type'] = 'pdd.ddk.goods.promotion.url.generate';
        return $this->_doRequest($aParameter);
    }

    /**
     * 公共参数
     * @return type
     */
    private function _getCommonParameter() {
        return [
            'client_id' => $this->clientId,
            'access_token' => '',
            'timestamp' => time(),
            'data_type' => 'JSON',
            'version' => 'V1',
        ];
    }

    /**
     * 通用发起请求封装
     * @param type $aInputData
     * @return type
     */
    private function _doRequest($aInputData = []) {
        $aData = $this->_getCommonParameter();
        $aData = array_merge($aData, $aInputData);
        $aData['sign'] = $this->_getSign($aData);
        $aResult = self::post($this->host, $aData);
        return $aResult ? json_decode($aResult, true) : false;
    }

    /**
     * 签名
     * @param type $aData
     * @return type
     */
    private function _getSign($aData) {
        ksort($aData);
        $str = $this->clientSecret;
        foreach ($aData as $key => $val) {
            $str.= $key . $val;
        }
        $str.= $this->clientSecret;
        return strtoupper(md5($str));
    }

    /**
     * 以get访问模拟访问
     * @param string $url 访问URL
     * @param array $query GET数
     * @param array $options
     * @return bool|string
     */
    public static function get($url, $query = [], $options = []) {
        $options['query'] = $query;
        return self::doRequest('get', $url, $options);
    }

    /**
     * 以post访问模拟访问
     * @param string $url 访问URL
     * @param array $data POST数据
     * @param array $options
     * @return bool|string
     */
    public static function post($url, $data = [], $options = []) {
        $options['data'] = $data;
        return self::doRequest('post', $url, $options);
    }

    /**
     * CURL模拟网络请求
     * @param string $method 请求方法
     * @param string $url 请求方法
     * @param array $options 请求参数[headers,data,ssl_cer,ssl_key]
     * @return bool|string
     */
    protected static function doRequest($method, $url, $options = []) {
        $curl = curl_init();
        // GET参数设置
        if (!empty($options['query'])) {
            $url .= (stripos($url, '?') !== false ? '&' : '?') . http_build_query($options['query']);
        }
        // CURL头信息设置
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        // POST数据设置
        if (strtolower($method) === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        }
        // 证书文件设置
        if (!empty($options['ssl_cer'])) {
            if (file_exists($options['ssl_cer'])) {
                curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLCERT, $options['ssl_cer']);
            } else {
                throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_cer]");
            }
        }
        // 证书文件设置
        if (!empty($options['ssl_key'])) {
            if (file_exists($options['ssl_key'])) {
                curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLKEY, $options['ssl_key']);
            } else {
                throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_key]");
            }
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        return (intval($status["http_code"]) === 200) ? $content : false;
    }

}
