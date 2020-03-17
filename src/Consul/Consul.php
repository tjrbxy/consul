<?php
/**
 * author : abel.tang
 * Date: 2019-12-27  16:11
 */

namespace Consul;

use SensioLabs\Consul\ServiceFactory;

/**
 * Class Consul
 * @package Consul
 */
class Consul
{
    private $baseUri = '';
    private $token = '';

    public function __construct($url = 'http://10.10.10.4:8500', $token = '')
    {
        $this->baseUri = $url;
        $this->token = $token;
    }

    public function get($hostName = '')
    {
        try {
            if (empty($hostName)) {
                $hostName = $_SERVER['HTTP_HOST'];
            }
            $dataList = '';
            $path = "/gaodun/cache/" . $hostName . "/consul.php";
            if (is_file($path)) {
                $dataList = unserialize(@file_get_contents($path));
            }
            if (!empty($dataList) && is_array($dataList)) {
                return $dataList;
            }
            $sf = new ServiceFactory([
                'base_uri' => $this->baseUri,
                'headers' => ['X-Consul-Token' => $this->token]
            ]);
            $kv = $sf->get('kv');
            $val = $kv->get('config', ['keys' => true]);
            $data = $val->json();
            $dataList = [];
            foreach ($data as $value) {
                if (substr($value, -1) != '/') {
                    $tmp = explode('/', $value);
                    $result = $kv->get($value, ['raw' => true])->getBody();
                    $tmpStr = $tmp[count($tmp) - 1];
                    $dataList[$tmpStr] = $result;
                }
            }
            @mkdir("/gaodun/cache/" . $hostName);
            file_put_contents($path, serialize($dataList));
            return $dataList;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}