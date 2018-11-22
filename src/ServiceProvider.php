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
                config('qcloud_image.appid'),
                config('qcloud_image.secret_id'),
                config('qcloud_image.secret_key'),
                config('qcloud_image.bucket')
            );
        });

        $this->app->alias(QcloudImage::class, 'QcloudImage');
    }

    public function boot()
    {
        $this->publishes([__DIR__.'/config/qcloud_image.php' => config_path('qcloud_image.php')], 'qcloud-image');
    }

    public function provides()
    {
        return [QcloudImage::class, 'QcloudImage'];
    }
}