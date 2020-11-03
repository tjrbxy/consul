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
    private $path = '/gaodun/cache';

    public function __construct($url = 'http://10.10.10.4:8500', $token = '')
    {
        $this->baseUri = $url;
        $this->token = $token;
    }

    public function get($hostName = 'Cli')
    {
        try {
            if (!$this->is_cli()) {
                $hostName = $_SERVER['HTTP_HOST'];
            }
            $this->checkDir();
            $dataList = '';
            $path = $this->path.'/' . $hostName . "/consul.php";
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
            @mkdir($this->path.'/' . $hostName);
            file_put_contents($path, serialize($dataList));
            return $dataList;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function is_cli()
    {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    /**
     * 检查目录是否存在
     */
    private function checkDir(){
        if (!is_dir($this->path)){
            $this->path = '/tmp';
        }
    }
}