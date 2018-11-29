<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 2018/11/22
 * Time: 15:43
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