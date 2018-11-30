<h1 align="center"> Laravel-Qcloud-Image </h1>

<p align="center"> 腾讯云云智AI图像服务的SDK for Laravel.</p>

[![StyleCI](https://github.styleci.io/repos/158688236/shield?branch=master)](https://github.styleci.io/repos/158688236)

## 安装

```
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

2. 在 `config/services.php` 文件中添加如下配置
```
'qcloud_image' => [
    'appid' => env('QCLOUD_IMAGE_APPID'),
    'secret_id' => env('QCLOUD_IMAGE_SECRET_ID'),
    'secret_key' => env('QCLOUD_IMAGE_SECRET_KEY'),
    'bucket' => env('QCLOUD_IMAGE_BUCKET', ''),
],
```

3. 在 `.env` 文件中配置相关参数

```
QCLOUD_IMAGE_APPID=xxxxx
QCLOUD_IMAGE_SECRET_ID=xxxxxxxxxxx
QCLOUD_IMAGE_SECRET_KEY=xxxxxxxxx
# 历史遗留字段，可不填
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

## API

### 人脸识别

#### 人脸检测
检测给定图片中的所有人脸( Face )的位置和相应的面部属性，位置包括(x，y，w，h)，面部属性包括性别( gender )、年龄( age )、表情( expression )、魅力( beauty )、眼镜( glass )和姿态 (pitch，roll，yaw )。
```
// 单个图片 URL， mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'), 1);
// 单个图片 file,mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('file'=>'F:\pic\face1.jpg'),0);
// 单个图片内容，mode:1 为检测最大的人脸，0 为检测所有人脸
$qcloudImage->faceDetect(array('buffer'=>file_get_contents('F:\pic\face1.jpg')), 1);
```

#### 五官定位
对请求图片进行五官定位，计算构成人脸轮廓的 88 个点，包括眉毛（左右各 8 点）、眼睛（左右各 8 点）、鼻子（ 13 点）、嘴巴（ 22 点）、脸型轮廓（ 21 点）。
```
// 单个图片Url,mode:1为检测最大的人脸 , 0为检测所有人脸
$qcloudImage->faceShape(array('url'=>'YOUR URL'),1);
// 单个图片file,mode:1为检测最大的人脸 , 0为检测所有人脸
$qcloudImage->faceShape(array('file'=>'F:\pic\face1.jpg'),0);
// 单个图片内容,mode:1为检测最大的人脸 , 0为检测所有人脸
$qcloudImage->faceShape(array('buffer'=>file_get_contents('F:\pic\face1.jpg')), 1);
```

#### 人脸对比
计算两个 Face 的相似性以及五官相似度。
```
// 两个对比图片的文件url
$qcloudImage->faceCompare(array('url'=>"YOUR URL A"), array('url'=>'YOUR URL B'));
// 两个对比图片的文件file
$qcloudImage->faceCompare(array('file'=>'F:\pic\yang.jpg'), array('file'=>'F:\pic\yang2.jpg'));
// 两个对比图片的文件内容
$qcloudImage->faceCompare(array('file'=>'F:\pic\yang.jpg'), array('file'=>'F:\pic\yang2.jpg'));
```

#### 个体信息管理
```
// 个体创建,创建一个Person，并将Person放置到group_ids指定的组当中，不存在的group_id会自动创建。
// 创建一个Person, 使用图片url
$qcloudImage->faceNewPerson('person1111', array('group11',), array('url'=>'YOUR URL'), 'xiaoxin');
// 创建一个Person, 使用图片file
$qcloudImage->faceNewPerson('person2111', array('group11',), array('file'=>'F:\pic\hot1.jpg'));
// 创建一个Person, 使用图片内容
$qcloudImage->faceNewPerson('person3111', array('group11',), array('buffer'=>file_get_contents('F:\pic\zhao1.jpg')));

// 增加人脸,将一组Face加入到一个Person中。
// 将单个或者多个Face的url加入到一个Person中
$qcloudImage->faceAddFace('person1111', array('urls'=>array('YOUR URL A', 'YOUR URL B')));
// 将单个或者多个Face的file加入到一个Person中
$qcloudImage->faceAddFace('person2111', array('files'=>array('F:\pic\yang.jpg', 'F:\pic\yang2.jpg')));
// 将单个或者多个Face的文件内容加入到一个Person中
$qcloudImage->faceAddFace('person3111', array('buffers'=>array(file_get_contents('F:\pic\yang.jpg'), file_get_contents('F:\pic\yang2.jpg'))));

// 删除人脸,删除一个person下的face
$qcloudImage->faceDelFace('person1', array('12346'));
	
// 设置信息
$qcloudImage->faceSetInfo('person1', 'fanbing');

// 获取信息
$qcloudImage->faceGetInfo('person1');

// 获取所有组列表
$qcloudImage->faceGetGroupIds();

// 获取组下所有人列表
$qcloudImage->faceGetPersonIds('group1');

// 获取人下所有人脸列表
$qcloudImage->faceGetFaceIds('person1');

// 获取人脸信息
$qcloudImage->faceGetFaceInfo('1704147773393235686');

// 删除个人
$qcloudImage->faceDelPerson('person11');

// 新增group_id
$qcloudImage->faceAddGroupIds('person11', array('group2', 'group3'));

// 删除group_id
$qcloudImage->faceDelGroupIds('person11', array('group2', 'group3'));
```

#### 人脸验证
给定一个图片和一个 Person ，检查是否是同一个人。
```
// 单个图片Url
$qcloudImage->faceVerify('person1', array('url'=>'YOUR URL'));
// 单个图片file
$qcloudImage->faceVerify('person3111', array('file'=>'F:\pic\yang3.jpg'));
// 单个图片内容
$qcloudImage->faceVerify('person3111', array('buffer'=>file_get_contents('F:\pic\yang3.jpg')));
```

#### 人脸检索
对一张待识别的人脸图片，在一个或多个 group 中识别出最相似的 Top5 person 作为其身份返回，返回的 Top5 中按照相似度从大到小排列。
```
// 单个文件url
$qcloudImage->faceIdentify('group1', array('url'=>'YOUR URL'));
// 单个文件file
$qcloudImage->faceIdentify('group11', array('file'=>'F:\pic\yang3.jpg'));
// 单个文件内容
$qcloudImage->faceIdentify('group11', array('buffer'=>file_get_contents('F:\pic\yang3.jpg')));
```

#### 多脸检索
对一张包含多个待识别的人脸的图片，在一个 group 或多个 groups 中识别出最相似的 person 作为其身份返回，返回的 Top5 中按照相似度从大到小排列。
```
// 单张文件url，一个group
$qcloudImage->multidentify(array('url'=>'YOUR URL'), array('group_id' => 'group111'));
// 单张文件url，多个groups
$qcloudImage->multidentify(array('url'=>'YOUR URL'), array('group_ids' => array('group111','group222')));
// 单个文件file，一个group
$qcloudImage->multidentify(array('file'=>'F:\pic\yang3.jpg'), array('group_id' => 'group111'));
// 单个文件file，多个groups
$qcloudImage->multidentify(array('file'=>'F:\pic\yang3.jpg'), array('group_ids' => array('group111','group222')));
// 单个文件内容，一个group
$qcloudImage->multidentify(array('buffer'=>file_get_contents('F:\pic\yang3.jpg'), array('group_id' => 'group111'));
// 单个文件内容，多个groups
$qcloudImage->multidentify(array('buffer'=>file_get_contents('F:\pic\yang3.jpg'), array('group_ids' => array('group111','group222')));

```

### 人脸核身

#### 人脸静态活体检测
对用户上传的静态照片进行人脸活体检测，判断是否为活体
```
// 单个文件url
$qcloudImage->liveDetectPicture(array('url'=>'YOUR URL'));
// 单个文件file
$qcloudImage->liveDetectPicture(array('file'=>'F:\pic\yang3.jpg'));
// 单个文件内容
$qcloudImage->liveDetectPicture(array('buffer'=>file_get_contents('F:\pic\yang3.jpg')));
```

#### 用户上传照片身份信息核验
用于判断给定一张照片与身份证号和姓名对应的登记照的人脸相似度，即判断给定照片中的人与身份证上的人是否为同一人。
```
// 身份证号，身份证姓名，单个文件url
$qcloudImage->liveDetectPicture('idcardNumber', 'idcardName', array('url'=>'YOUR URL'));
// 身份证号，身份证姓名，单个文件file
$qcloudImage->liveDetectPicture('idcardNumber', 'idcardName', array('file'=>'F:\pic\yang3.jpg'));
// 身份证号，身份证姓名，单个文件内容
$qcloudImage->liveDetectPicture('idcardNumber', 'idcardName', array('buffer'=>file_get_contents('F:\pic\yang3.jpg')));
```

#### 活体检测-获取唇语验证码
获取一个唇语验证字符串，用于录制视频，进行活体检测。
```
$obj = $qcloudImage->faceLiveGetFour();
var_dump ($obj);
$validate_data = $obj['data']['validate_data'];
```

#### 活体检测-视频与用户照片的比对
判断录制的唇语视频中人物是否为真人（活体检测），同时判断唇语视频中的人脸与给定的一张人脸照片的人脸相似度，即判断视频中的人与给定一张照片的人是否为同一人。
````
$qcloudImage->faceLiveDetectFour($validate_data, array('file'=>'F:\pic\ZOE_0171.mp4'), False, array('F:\pic\idcard.jpg'));
````

#### 活体检测-视频与身份证高清照片的比对
判断录制的唇语视频中人物是否为真人（活体检测），同时判断唇语视频中的人脸与身份证号和姓名对应的登记照的人脸相似度，即判断视频中的人与身份证上的人是否为同一人。
```
$qcloudImage->faceIdCardLiveDetectFour($validate_data, array('file'=>'F:\pic\ZOE_0171.mp4'), 'xxxxxxxxxxx', 'xxxxxxxxxxx');
```

### 智能鉴黄

```
// 单个或多个图片 URL
$qcloudImage->pornDetect(array('urls'=> array('http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png',
            "http://jiangsu.china.com.cn/uploadfile/2015/1102/1446443026382534.jpg")));
// 单个或多个图片 file
$qcloudImage->pornDetect(array('files'=> array('F:\pic\你好.jpg','G:\pic\test2.jpg')));
```

### 文字识别-ORC

#### 身份证识别
```

// 单个或多个图片 URL，cardType: 0为身份证有照片的一面，1为身份证有国徽的一面
$qcloudImage->namecardV2Detect(array('urls'=>array('http://imgs.focus.cn/upload/sz/5876/a_58758051.jpg',
                            'http://img5.iqilu.com/c/u/2013/0530/1369896921237.jpg')), 0);
// 单个或多个图片 file，cardType: 0为身份证有照片的一面，1为身份证有国徽的一面
$qcloudImage->namecardV2Detect(array('files'=>array('F:\pic\id6zheng.jpg', 'F:\pic\id2zheng.jpg')), 1);
// 单个或多个图片内容，cardType: 0为身份证有照片的一面，1为身份证有国徽的一面
$qcloudImage->namecardV2Detect(array('buffers'=>array(file_get_contents('F:\pic\id6_zheng.jpg'),
                            file_get_contents('F:\pic\id2_zheng.jpg'))), 0);

```

#### 名片识别（V2）
```
// 单个或多个图片 URL，
$qcloudImage->idcardDetect(array('urls'=>array('http://imgs.focus.cn/upload/sz/5876/a_58758051.jpg',
                            'http://img5.iqilu.com/c/u/2013/0530/1369896921237.jpg')));
// 单个或多个图片 file，
$qcloudImage->idcardDetect(array('files'=>array('F:\pic\id6zheng.jpg', 'F:\pic\id2zheng.jpg')));
// 单个或多个图片内容，cardType: 
$qcloudImage->idcardDetect(array('buffers'=>array(file_get_contents('F:\pic\id6_zheng.jpg'),                                                                
                            file_get_contents('F:\pic\id2_zheng.jpg'))));
```

#### 行驶证驾驶证识别
```
// 单个图片 URL， type:0 表示行驶证，1 表示驾驶证，2 表示行驶证副页。
$qcloudImage->drivingLicence(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'), 1);
// 单个图片 file,type:0 表示行驶证，1 表示驾驶证，2 表示行驶证副页。
$qcloudImage->drivingLicence(array('file'=>'F:\pic\face1.jpg'),0);
// 单个图片内容，type:0 表示行驶证，1 表示驾驶证，2 表示行驶证副页。
$qcloudImage->drivingLicence(array('buffer'=>file_get_contents('F:\pic\face1.jpg')), 1);
```

#### 车牌号识别
```
// 单个图片 URL
$qcloudImage->plate(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'));
// 单个图片 file
$qcloudImage->plate(array('file'=>'F:\pic\face1.jpg'));
// 单个图片内容
$qcloudImage->plate(array('buffer'=>file_get_contents('F:\pic\face1.jpg')));
```

#### 银行卡识别
```
// 单个图片 URL
$qcloudImage->bankcard(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'));
// 单个图片 file
$qcloudImage->bankcard(array('file'=>'F:\pic\face1.jpg'));
// 单个图片内容
$qcloudImage->bankcard(array('buffer'=>file_get_contents('F:\pic\face1.jpg')));
```

#### 营业执照识别
```
// 单个图片 URL
$qcloudImage->bizlicense(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'));
// 单个图片 file
$qcloudImage->bizlicense(array('file'=>'F:\pic\face1.jpg'));
// 单个图片内容
$qcloudImage->bizlicense(array('buffer'=>file_get_contents('F:\pic\face1.jpg')));
```

#### 通用印刷体识别
```
// 单个图片 URL
$qcloudImage->general(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'));
// 单个图片 file
$qcloudImage->general(array('file'=>'F:\pic\face1.jpg'));
// 单个图片内容
$qcloudImage->general(array('buffer'=>file_get_contents('F:\pic\face1.jpg')));
```

#### 手写体识别
```
// 单个图片 URL
$qcloudImage->handwriting(array('url'=>'http://img3.a0bi.com/upload/ttq/20160814/1471155260063.png'));
// 单个图片 file
$qcloudImage->handwriting(array('file'=>'F:\pic\face1.jpg'));
// 单个图片内容
$qcloudImage->handwriting(array('buffer'=>file_get_contents('F:\pic\face1.jpg')));
```

## 参考

[云智AI应用服务智能图像 SDK V2.0](https://github.com/tencentyun/image-php-sdk-v2.0)

[PHP 扩展包实战教程 - 从入门到发布](https://laravel-china.org/courses/creating-package)

## License

MIT