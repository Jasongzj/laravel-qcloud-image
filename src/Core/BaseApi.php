<?php

/*
 * This file is part of the jasongzj/laravel-qcloud-image.
 *
 * (c) jasongzj <jasongzj@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jasongzj\LaravelQcloudImage\Core;

use GuzzleHttp\RequestOptions;
use Jasongzj\LaravelQcloudImage\QcloudImage;

abstract class BaseApi
{
    /**
     * @var QcloudImage
     */
    protected $image;

    public function __construct(QcloudImage $image)
    {
        $this->image = $image;
    }

    /**
     * 发送 Json 请求
     *
     * @param $reqUrl
     * @param $headers
     * @param $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function jsonRequest($reqUrl, $headers, $data)
    {
        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::JSON => $data,
        ];

        return $this->image->getHttpClient()->post($reqUrl, $options);
    }

    /**
     * 发送 multiple/form-data 请求
     *
     * @param $reqUrl
     * @param $headers
     * @param $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function multipleFormDataRequest($reqUrl, $headers, $data)
    {
        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::MULTIPART => $data,
        ];

        return $this->image->getHttpClient()->post($reqUrl, $options);
    }
}
