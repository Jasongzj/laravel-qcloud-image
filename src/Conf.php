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

class Conf
{
    const SERVER_ADDR = 'service.image.myqcloud.com';

    const SERVER_ADDR2 = 'recognition.image.myqcloud.com';

    const VERSION = '2.0.0';

    private $SCHEME = 'https';

    private $HOST = self::SERVER_ADDR2;

    private $REQ_TIMEOUT = 60;

    public function setTimeout($timeout)
    {
        if ($timeout > 0) {
            $this->REQ_TIMEOUT = $timeout;
        }
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

    public function timeout()
    {
        return $this->REQ_TIMEOUT;
    }

    public function buildUrl($uri)
    {
        return $this->SCHEME.'://'.$this->HOST.'/'.ltrim($uri, '/');
    }

    public static function getUa($appid = null)
    {
        $ua = 'CIPhpSDK/'.self::VERSION.' ('.php_uname().')';
        if ($appid) {
            $ua .= " User($appid)";
        }

        return $ua;
    }
}
