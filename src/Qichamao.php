<?php

namespace Qichamao;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;

use function GuzzleHttp\json_decode;

/**
 * 企查猫主类
 * @package Qichamao
 * @method \Qichamao\Data\CompanyItem[] orgCompany($data=['companyName'=>'xx','areaCode'=>43,'page'=>1,'pagesize'=>100]) 根据企业名或地区搜索企业
 */
class Qichamao
{
    /**
     * @var string
     */
    protected $appkey;

    /**
     * @var string
     */
    protected $seckey;

    /**
     * @param string $appkey
     * @param string $seckey
     */
    public function __construct($appkey, $seckey)
    {
        $this->appkey = $appkey;
        $this->seckey = $seckey;
    }

    /**
     * 获取企查猫的token
     *
     * @return string
     */
    protected function getToken()
    {
        $token = Cache::get(__CLASS__ . '_token', function () {
            $http = new Client();
            $request = $http->get('https://api.qianzhan.com/OpenPlatformService/GetToken', [
                RequestOptions::QUERY => [
                    'type' => 'JSON',
                    'appkey' => $this->appkey,
                    'seckey' => $this->seckey,
                ]
            ]);

            $json = json_decode($request->getBody()->__toString());
            if (!isset($json->status) || $json->status != 200) {
                throw new Exception($json->message, $json->status);
            }
            if (!isset($json->result) || !isset($json->result->token)) {
                throw new Exception('未返回token', $json->status);
            }
            $token = $json->result->token;
            Cache::put(__CLASS__ . '_token', $json->result->token, 3600);
            return $token;
        });
        return $token;
    }

    /**
     * 魔法调用
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $http = new Client();
        $request = $http->get('https://api.qianzhan.com/OpenPlatformService/' . ucfirst($name), [
            RequestOptions::QUERY => array_merge([
                'type' => 'JSON',
                'token' => $this->getToken(),
            ], $arguments[0]),
        ]);

        $json = json_decode($request->getBody()->__toString());
        if (!isset($json->status)) {
            throw new Exception($request->getBody()->__toString(), 0);
        }

        if ($json->status == 101 || $json->status == 102) {
            Cache::forget(__CLASS__ . '_token');
            return $this->__call($name, $arguments);
        }

        if ($json->status != 200) {
            throw new Exception($json->message, $json->status);
        }

        return $json->result;
    }
}
