<?php

/*
 * This file is part of the jasongzj/laravel-qcloud-image.
 *
 * (c) jasongzj <jasongzj@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jasongzj\LaravelQcloudImage;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Jasongzj\LaravelQcloudImage\Modules\FaceRecognition;
use Jasongzj\LaravelQcloudImage\Modules\FaceIn;
use Jasongzj\LaravelQcloudImage\Modules\ORC;
use Jasongzj\LaravelQcloudImage\Modules\PornIdentification;
use Jasongzj\LaravelQcloudImage\Modules\Tag;
use ReflectionClass;

/**
 * Class QcloudImage
 * @package Jasongzj\LaravelQcloudImage
 * @property FaceIn
 * @property FaceRecognition
 * @property ORC
 * @property PornIdentification
 * @property Tag
 */
class QcloudImage
{
    const SERVER_ADDR = 'service.image.myqcloud.com';

    const SERVER_ADDR2 = 'recognition.image.myqcloud.com';

    const VERSION = '2.0.0';

    protected $appId;

    protected $secretId;

    protected $secretKey;

    private $SCHEME = 'https';

    private $HOST = self::SERVER_ADDR2;

    /**
     * @var array
     */
    protected $apiInstances = [];

    /**
     * @var Client
     */
    protected $httpClient = null;

    public function __construct($appId, $secretId, $secretKey, $guzzleOptions = [])
    {
        $this->setHttpClient($guzzleOptions);
        $this->setAuth($appId, $secretId, $secretKey);
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws \ReflectionException
     */
    public function __get($name)
    {
        $apiMap = $this->getApiMap();
        if (!isset($apiMap[$name])) {
            return null;
        }

        if (!isset($this->apiInstances[$name])) {
            $this->apiInstances[$name] = (new ReflectionClass($apiMap[$name]))->newInstanceArgs([$this]);
        }

        return $this->apiInstances[$name];
    }

    protected function getApiMap()
    {
        return [
            'faceRecognition' => FaceRecognition::class,
            'pornIdentification' => PornIdentification::class,
            'tag' => Tag::class,
            'ORC' => ORC::class,
            'faceIn' => FaceIn::class,
        ];
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function setHttpClient($guzzleOptions = [])
    {
        $guzzleOptions[RequestOptions::HTTP_ERRORS] = false;

        if (!$this->httpClient) {
            $this->httpClient = new Client($guzzleOptions);
        }

        return $this;
    }

    public function setAuth($appId, $secretId, $secretKey)
    {
        $this->appId = $appId;
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function useHttp()
    {
        $this->SCHEME = 'http';
    }

    public function useHttps()
    {
        $this->SCHEME = 'https';
    }

    public function useNewDomain()
    {
        $this->HOST = self::SERVER_ADDR2;
    }

    public function useOldDomain()
    {
        $this->HOST = self::SERVER_ADDR;
    }

    public function buildUrl($uri)
    {
        return $this->SCHEME.'://'.$this->HOST.'/'.ltrim($uri, '/');
    }

    /**
     * 获取鉴权签名
     * @param string $bucket
     * @param int $howlong
     * @return bool|string
     */
    public function getSign($bucket = '', $howlong = 30)
    {
        if ($howlong <= 0) {
            return false;
        }

        $now = time();
        $expiration = $now + $howlong;
        $random = rand();

        $plainText = 'a='.$this->appId."&b=$bucket&k=".$this->secretId."&e=$expiration&t=$now&r=$random&f=";
        $bin = hash_hmac('SHA1', $plainText, $this->secretKey, true);

        return base64_encode($bin.$plainText);
    }
}
