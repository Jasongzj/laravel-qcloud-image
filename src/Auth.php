<?php
/**
 * This file is part of the jasongzj/laravel-qcloud-image.
 *
 * (c) jasongzj <jasongzj@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jasongzj\LaravelQcloudImage;


class Auth
{
    private $appId;
    private $secretId;
    private $secretKey;

    public function __construct($appId, $secretId, $secretKey)
    {
        $this->appId = $appId;
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
    }

    /**
     * Return the appId
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Create reusable signature.
     * This signature will expire at time()+$howlong timestamp.
     * Return the signature on success.
     * Return false on fail.
     */
    public function getSign($bucket = '', $howlong = 30)
    {
        if ($howlong <= 0) {
            return false;
        }

        $now = time();
        $expiration = $now + $howlong;
        $random = rand();

        $plainText = "a=" . $this->appId . "&b=$bucket&k=" . $this->secretId . "&e=$expiration&t=$now&r=$random&f=";
        $bin = hash_hmac('SHA1', $plainText, $this->secretKey, true);
        return base64_encode($bin . $plainText);
    }

}