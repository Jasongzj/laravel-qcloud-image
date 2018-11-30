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


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(QcloudImage::class, function () {
            return new QcloudImage(
                config('services.qcloud_image.appid'),
                config('services.qcloud_image.secret_id'),
                config('services.qcloud_image.secret_key'),
                config('services.qcloud_image.bucket')
            );
        });

        $this->app->alias(QcloudImage::class, 'QcloudImage');
    }


    public function provides()
    {
        return [QcloudImage::class, 'QcloudImage'];
    }
}