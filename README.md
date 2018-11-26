<h1 align="center"> Laravel-Qcloud-Image </h1>

<p align="center"> 腾讯云云智AI图像服务的SDK for Laravel.</p>


## 安装

```shell
$ composer require jasongzj/laravel-qcloud-image -vvv
```

## 配置

1. 在 config/app.php 注册 ServiceProvider (Laravel 5.5 无需手动注册)

```
'providers' => [
    // ...
    Jasongzj\LaravelQcloudImage\ServiceProvider::class,
]
```

2. 创建配置文件

```
php artisan vendor:publish --provider="Jasongzj\LaravelQcloudImage\ServiceProvider"
```

3. 在 `.env` 文件中配置相关参数

```
QCLOUD_IMAGE_APPID=xxxxx
QCLOUD_IMAGE_SECRET_ID=xxxxxxxxxxx
QCLOUD_IMAGE_SECRET_KEY=xxxxxxxxx
QCLOUD_IMAGE_BUCKET=xxxxxxx
```

## 使用

#### 方法参数注入

```
use Jasongzj\LaravelQcloudImage\QcloudImage;
·
·
·
    public function detect(QcloudImage $qcloudImage)
    {
        $image = array('url' => 'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png');
        $qcloudImage->detect($image, 1);
    }
·
·
·
```

#### 服务名访问

```
use Jasongzj\LaravelQcloudImage\QcloudImage;
·
·
·
    public function detect()
    {
        
        $image = array('url' => 'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png');
        app(QcloudImage::class)->detect($image, 1);
    }
·
·
·
```

### API

#### 人脸识别

##### 人脸检测

```
//单个图片 URL， mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'), 1);
//单个图片 file,mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('file'=>'F:\pic\face1.jpg'),0);
//单个图片内容，mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('buffer'=>file_get_contents('F:\pic\face1.jpg')), 1);
```

#### 智能鉴黄

```
//单个或多个图片 URL
$qcloudImage->pornDetect(
    array('urls'=>
        array('http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png',
            "http://jiangsu.china.com.cn/uploadfile/2015/1102/1446443026382534.jpg")
    )
);
//单个或多个图片 file
$qcloudImage->pornDetect(
    array('files'=>
        array('F:\pic\你好.jpg','G:\pic\test2.jpg')
    )
);
```

## 参考

[云智AI应用服务智能图像 SDK V2.0](https://github.com/tencentyun/image-php-sdk-v2.0)

## License

MIT