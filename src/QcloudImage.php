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
use GuzzleHttp\Exception\ClientException;
use Jasongzj\LaravelQcloudImage\Exceptions\HttpException;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidArgumentException;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException;

class QcloudImage
{
    protected $auth;

    protected $conf;

    protected $guzzleOptions = [];

    public function __construct($appId, $secretId, $secretKey)
    {
        $this->auth = new Auth($appId, $secretId, $secretKey);
        $this->conf = new Conf();
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function useHttp()
    {
        $this->conf->useHttp();
    }

    public function useHttps()
    {
        $this->conf->useHttps();
    }

    public function setTimeout($timeout)
    {
        $this->conf->setTimeout($timeout);
    }

    /**
     * 使用新服务器域名 recognition.image.myqcloud.com<br>
     * <br>
     * 如果你:<br>
     * 1.正在使用人脸识别系列功能( https://cloud.tencent.com/product/FaceRecognition/developer )<br>
     * 2.并且是通过旧域名访问的<br>
     * 那么: 请继续使用旧域名.
     */
    public function useNewDomain()
    {
        $this->conf->useNewDomain();
    }

    /**
     * 使用旧服务器域名 recognition.image.myqcloud.com<br>
     * <br>
     * 如果你:<br>
     * 1.正在使用人脸识别系列功能( https://cloud.tencent.com/product/FaceRecognition/developer )<br>
     * 2.并且是通过旧域名访问的<br>
     * 那么: 请继续使用旧域名.
     */
    public function useOldDomain()
    {
        $this->conf->useOldDomain();
    }

    /**
     * 黄图识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    urls    array: 指定图片的url数组
     *                                    files   array: 指定图片的路径数组
     *                                    以上两种指定其一即可，如果指定多个，则优先使用urls，其次 files
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function pornDetect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/detection/pornDetect');

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->sendJsonRequest($reqUrl, $param);
        }

        if (isset($picture['files'])) {
            $param = $this->baseMultiParams();
            $index = 0;

            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $param[] = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r'),
                ];
                ++$index;
            }
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 标签识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    url    array: 指定图片的url数组
     *                                    file   array: 指定图片的路径数组
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function tagDetect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/v1/detection/imagetag_detect');

        if (isset($picture['url'])) {
            $param = $this->baseJsonParams();
            $param['url'] = $picture['url'];

            return $this->sendJsonRequest($reqUrl, $param);
        }

        if (isset($picture['file'])) {
            $filePath = $this->getFileRealPath($picture['file']);
            $files = [
                'name' => 'image',
                'contents' => base64_encode(fopen($filePath, 'r')),
            ];
        } elseif (isset($picture['buffer'])) {
            $files = [
                'name' => 'image',
                'contents' => base64_encode($picture['buffer']),
            ];
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        $param = $this->baseMultiParams();
        array_push($param, $files);

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    // ORC 模块开始 //

    /**
     * 身份证识别.
     *
     * @param array(associative) $picture  识别的图片
     *                                     urls    array: 指定图片的url数组
     *                                     files   array: 指定图片的路径数组
     *                                     buffers array: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param int                $cardType 0为身份证有照片的一面，1为身份证有国徽的一面
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function idcardDetect($picture, $cardType = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        if (0 !== $cardType && 1 !== $cardType) {
            throw new InvalidArgumentException('param cardType error');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/idcard');

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['card_type'] = $cardType;
            $param['url_list'] = $picture['urls'];

            return $this->sendJsonRequest($reqUrl, $param);
        }

        $param = $this->baseMultiParams();
        $param[] = [
            'name' => 'card_type',
            'contents' => $cardType,
        ];
        if (isset($picture['files'])) {
            $index = 0;
            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $param[] = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r'),
                ];
                ++$index;
            }
        } elseif (isset($picture['buffers'])) {
            $index = 0;
            foreach ($picture['buffers'] as $buffer) {
                $param[] = [
                    'name' => "image[$index]",
                    'contents' => $buffer,
                ];
                ++$index;
            }
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 名片识别v2.
     *
     * @param array(associative) $picture 识别的图片
     *                                    urls    array: 指定图片的url数组
     *                                    files   array: 指定图片的路径数组
     *                                    buffers array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function namecardV2Detect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/businesscard');

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->sendJsonRequest($reqUrl, $param);
        }

        $param = $this->baseMultiParams();

        if (isset($picture['files'])) {
            $index = 0;
            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $param[] = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r'),
                ];
                ++$index;
            }
        } elseif (isset($picture['buffers'])) {
            $index = 0;
            foreach ($picture['buffers'] as $buffer) {
                $param[] = [
                    'name' => "image[$index]",
                    'contents' => $buffer,
                ];
                ++$index;
            }
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 行驶证驾驶证识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    url    array: 指定图片的url数组
     *                                    file   array: 指定图片的路径数组
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param int                $type    表示识别类型，0 表示行驶证，1 表示驾驶证，2 表示行驶证副页
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function drivingLicence($picture, $type = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $type && 1 !== $type) {
            throw new InvalidArgumentException('param type error');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/drivinglicence');

        $options = ['type' => $type];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 车牌号识别.
     *
     * @param array(associative) $picture 车牌号的图片
     *                                    url    array: 指定图片的url
     *                                    file   array: 指定图片的路径
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function plate($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/plate');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 银行卡识别.
     *
     * @param array(associative) $picture 银行卡的图片
     *                                    url    array: 指定图片的url
     *                                    file   array: 指定图片的路径
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function bankcard($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/bankcard');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 营业执照识别.
     *
     * @param array(associative) $picture 营业执照图片
     *                                    url    array: 指定图片的url
     *                                    file   array: 指定图片的路径
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function bizlicense($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/bizlicense');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 通用印刷体识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    url    array: 指定图片的url
     *                                    file   array: 指定图片的路径
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function general($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/general');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 手写体识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    url    array: 指定图片的url
     *                                    file   array: 指定图片的路径
     *                                    buffer array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function handwriting($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/handwriting');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    // ORC 模块结束 //

    // 人脸识别 模块开始 //

    /**
     * 检测图中的人脸（人脸检测）.
     *
     * @param array(associative) $picture 人脸图片
     *                                    url    string: 指定图片的url
     *                                    file   string: 指定图片的路径
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param int                $mode    检测模式，0为检测所有人脸，1为检测最大的人脸
     *
     * @return string
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceDetect(array $picture, $mode = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $mode && 1 !== $mode) {
            throw new InvalidArgumentException('param mode error');
        }

        $reqUrl = $this->conf->buildUrl('/face/detect');
        $options = ['mode' => $mode];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 定位图中人脸的五官信息（五官定位）.
     *
     * @param array(associative) $picture 人脸图片
     *                                    url    string: 指定图片的url
     *                                    file   string: 指定图片的路径
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param int                $mode    检测模式，0为检测所有人脸，1为检测最大的人脸
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceShape($picture, $mode = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $mode && 1 !== $mode) {
            throw new InvalidArgumentException('param mode error');
        }
        $reqUrl = $this->conf->buildUrl('/face/shape');
        $options = [
            'mode' => $mode,
        ];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 对比两张图片是否是同一个人（人脸对比）.
     *
     * @param array(associative) $pictureA 人脸图片
     *                                     url    string: 指定图片的url
     *                                     file   string: 指定图片的路径
     *                                     buffer string: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param array(associative) $pictureB 人脸图片
     *                                     url    string: 指定图片的url
     *                                     file   string: 指定图片的路径
     *                                     buffer string: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceCompare($pictureA, $pictureB)
    {
        if (!$pictureA || !is_array($pictureA)) {
            throw new InvalidArgumentException('param pictureA must be array');
        }
        if (!$pictureB || !is_array($pictureB)) {
            throw new InvalidArgumentException('param pictureB must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/compare');

        if (isset($pictureA['url'])) {
            $param = $this->baseJsonParams();
            $param['urlA'] = $pictureA['url'];

            if (isset($pictureB['url'])) {
                $param['urlB'] = $pictureB['url'];
            } else {
                throw new InvalidArgumentException('param pictureB is illegal');
            }

            return $this->sendJsonRequest($reqUrl, $param);
        }

        $param = $this->baseMultiParams();
        if (isset($pictureA['file'])) {
            $filePath = $this->getFileRealPath($pictureA['file']);
            $param[] = [
                'name' => 'imageA',
                'contents' => fopen($filePath, 'r'),
            ];
        } elseif (isset($pictureA['buffer'])) {
            $param[] = [
                'name' => 'imageA',
                'contents' => $pictureA['buffer'],
            ];
        } else {
            throw new InvalidArgumentException('param pictureA is illegal');
        }
        if (isset($pictureB['file'])) {
            $filePath = $this->getFileRealPath($pictureB['file']);
            $param[] = [
                'name' => 'imageB',
                'contents' => fopen($filePath, 'r'),
            ];
        } elseif (isset($pictureB['buffer'])) {
            $param[] = [
                'name' => 'imageB',
                'contents' => $pictureA['buffer'],
            ];
        } else {
            throw new InvalidArgumentException('param pictureB is illegal');
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 创建Person.
     *
     * @param string             $personId   创建的Person的ID
     * @param array              $groupIds   创建的Person需要加入的Group
     * @param array(associative) $picture    创建的Person的人脸图片
     *                                       url    string: 指定图片的url
     *                                       file   string: 指定图片的路径
     *                                       buffer string: 指定图片的内容
     *                                       以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param string             $personName 创建的Person的名字
     * @param string             $tag        为创建的Person打标签
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceNewPerson($personId, $groupIds, $picture, $personName = null, $tag = null)
    {
        if (!$picture || !is_array($groupIds)) {
            throw new InvalidArgumentException('param groupIds must be array');
        }
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/newperson');

        $options['person_id'] = $personId;
        $options['group_ids'] = $groupIds;
        if ($personName) {
            $options['person_name'] = $personName;
        }
        if ($tag) {
            $options['tag'] = $tag;
        }

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 删除Person.
     *
     * @param string $personId 删除的Person的ID
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceDelPerson($personId)
    {
        $reqUrl = $this->conf->buildUrl('/face/delperson');
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 为Person 添加人脸.
     *
     * @param string             $personId 创建的Person的ID
     * @param array(associative) $pictures Person的人脸图片
     *                                     urls    array: 指定图片的url数组
     *                                     files   array: 指定图片的路径数组
     *                                     buffers array: 指定图片的内容数组
     *                                     以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，再次 buffers
     * @param string             $tag      为face打标签
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceAddFace($personId, $pictures, $tag = null)
    {
        if (!$pictures || !is_array($pictures)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/addface');

        if (isset($pictures['urls']) && is_array($pictures['urls'])) {
            $param = $this->baseJsonParams();
            $param['person_id'] = $personId;
            if ($tag) {
                $param['tag'] = $tag;
            }
            $param['urls'] = $pictures['urls'];

            $this->sendJsonRequest($reqUrl, $param);
        }

        $param = $this->baseMultiParams();
        $param[] = [
            'name' => 'person_id',
            'contents' => $personId,
        ];
        if ($tag) {
            $param[] = [
                'name' => 'tag',
                'contents' => $tag,
            ];
        }
        if (isset($pictures['files']) && is_array($pictures['files'])) {
            $index = 0;
            foreach ($pictures['files'] as $picture) {
                $filePath = $this->getFileRealPath($picture);
                $param[] = [
                    'name' => "images[$index]",
                    'contents' => fopen($filePath, 'r'),
                ];
                ++$index;
            }
        } elseif (isset($pictures['buffers']) && is_array($pictures['buffers'])) {
            $index = 0;
            foreach ($pictures['buffers'] as $buffer) {
                $param[] = [
                    'name' => "images[$index]",
                    'contents' => $buffer,
                ];
                ++$index;
            }
        } else {
            throw new InvalidArgumentException('param pictures is illegal');
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 删除face.
     *
     * @param string $personId 操作的Person的ID
     * @param array  $faceIds  删除的face的ID数组
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function faceDelFace($personId, $faceIds)
    {
        if (!is_array($faceIds)) {
            throw new InvalidArgumentException('param faceIds must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/delface');

        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['face_ids'] = $faceIds;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 设置信息（名字、标签）.
     *
     * @param string $personId   操作的Person的ID
     * @param string $personName Person的名字
     * @param string $tag        为Person打标签
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceSetInfo($personId, $personName = null, $tag = null)
    {
        $reqUrl = $this->conf->buildUrl('/face/setinfo');

        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        if ($personName) {
            $param['person_name'] = strval($personName);
        }
        if ($tag) {
            $param['tag'] = $tag;
        }

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 获取信息.
     *
     * @param string $personId 操作的Person的ID
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceGetInfo($personId)
    {
        $reqUrl = $this->conf->buildUrl('/face/getinfo');
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 获取appid下的所有组列表.
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceGetGroupIds()
    {
        $reqUrl = $this->conf->buildUrl('/face/getgroupids');
        $param = $this->baseJsonParams();

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 获取group下的所有person列表.
     *
     * @param string $groupId 操作的GroupID
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceGetPersonIds($groupId)
    {
        $reqUrl = $this->conf->buildUrl('/face/getpersonids');
        $param = $this->baseJsonParams();
        $param['group_id'] = $groupId;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 获取person的face列表.
     *
     * @param string $personId 操作的Person的ID
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceGetFaceIds($personId)
    {
        $reqUrl = $this->conf->buildUrl('/face/getfaceids');
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 获取face的信息.
     *
     * @param string $faceId 操作的FaceID
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceGetFaceInfo($faceId)
    {
        $reqUrl = $this->conf->buildUrl('/face/getfaceinfo');
        $param = $this->baseJsonParams();
        $param['face_id'] = $faceId;

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 为 person 新增 group_id.
     *
     * @param string $personId  创建的Person的ID
     * @param array  $groupIds  要新增的 group_ids
     * @param string $sessionId 会话 ID
     *
     * @return array Http 请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function faceAddGroupIds($personId, $groupIds, $sessionId = null)
    {
        if (!is_array($groupIds)) {
            throw new InvalidArgumentException('param groupids must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/addgroupids');
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['group_ids'] = $groupIds;
        if ($sessionId) {
            $param['session_id'] = $sessionId;
        }

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 为 person 删除 group_id.
     *
     * @param string $personId  人脸 ID
     * @param array  $groupIds  群组 Id
     * @param string $sessionId 会话ID
     *
     * @return array Http 请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function faceDelGroupIds($personId, $groupIds, $sessionId = null)
    {
        if (!is_array($groupIds)) {
            throw new InvalidArgumentException('param groupids must be array');
        }
        $reqUrl = $this->conf->buildUrl('face/delgroupids');
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['group_ids'] = $groupIds;
        if ($sessionId) {
            $param['session_id'] = $sessionId;
        }

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 识别指定的图片是不是指定的person（人脸验证）.
     *
     * @param string             $personId 需要对比的person
     * @param array(associative) $picture  人脸图片
     *                                     url    string: 指定图片的url
     *                                     file   string: 指定图片的路径
     *                                     buffer string: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceVerify($personId, $picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/verify');
        $options = [
            'person_id' => $personId,
        ];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 识别指定的图片属于哪个人（人脸检索）.
     *
     * @param array|string       $groupIds 需要对比的GroupId
     * @param array(associative) $picture  Person的人脸图片
     *                                     url    string: 指定图片的url
     *                                     file   string: 指定图片的路径
     *                                     buffer string: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceIdentify($groupIds, $picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/identify');
        if (!is_array($groupIds)) {
            $options = [
                'group_id' => $groupIds,
            ];
        } else {
            $options = [
                'group_ids' => $groupIds,
            ];
        }

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 多脸检索.
     *
     * @param array(associative) $picture  人脸图片
     *                                     url    string: 指定图片的url
     *                                     file   string: 指定图片的路径
     *                                     buffer string: 指定图片的内容
     *                                     以上三种指定其一即可，如果指定多个，则优先使用url，其次 file, 最后buffer
     * @param array|string       $groupIds 单个id 或者多个id的数组
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function multidentify($picture, $groupIds)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/multidentify');

        if (!is_array($groupIds)) {
            $options = [
                'group_id' => $groupIds,
            ];
        } else {
            $options = [
                'group_ids' => $groupIds,
            ];
        }

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    // 人脸识别 模块结束 //

    // 人脸核身 模块开始 //

    /**
     * 人脸静态活体检测.
     *
     * @param array(associative) $picture 人脸图片
     *                                    url    string: 指定图片的url
     *                                    file   string: 指定图片的路径
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function liveDetectPicture($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/livedetectpicture');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 检测图片中的人和给定的信息是否匹配.
     *
     * @param string             $idcardNumber 身份证号
     * @param string             $idcardName   姓名
     * @param array(associative) $picture      人脸图片
     *                                         url    string: 指定图片的url
     *                                         file   string: 指定图片的路径
     *                                         buffer string: 指定图片的内容
     *                                         以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceIdCardCompare($idcardNumber, $idcardName, $picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/idcardcompare');
        $options = [
            'idcard_number' => $idcardNumber,
            'idcard_name' => $idcardName,
        ];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 活体检测第一步：获取唇语（验证码）.
     *
     * @param string $seq 指定一个sessionId，若使用，请确保id唯一
     *
     * @return array http请求响应
     *
     * @throws HttpException
     */
    public function faceLiveGetFour($seq = null)
    {
        $reqUrl = $this->conf->buildUrl('/face/livegetfour');
        $param = $this->baseJsonParams();
        if ($seq) {
            $param['seq'] = strval($seq);
        }

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 活体检测第二步：检测--视频与用户视频比对.
     *
     * @param string             $validate    faceLiveGetFour获取的验证码
     * @param array(associative) $video       拍摄的视频
     *                                        file   string: 指定图片的路径
     *                                        buffer string: 指定图片的内容
     *                                        以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param bool               $compareFlag 是否将视频中的人和card图片比对
     * @param array(associative) $card        人脸图片
     *                                        file   string: 指定图片的路径
     *                                        buffer string: 指定图片的内容
     *                                        以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param string             $seq         指定一个sessionId，若使用，请确保id唯一
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceLiveDetectFour($validate, $video, $compareFlag, $card = null, $seq = null)
    {
        if (!is_array($video)) {
            throw new InvalidArgumentException('param video must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/livedetectfour');

        $param = $this->baseMultiParams();
        $param[] = [
            'name' => 'validate_data',
            'contents' => $validate,
        ];
        if (isset($video['file'])) {
            $videoFilePath = $this->getFileRealPath($video['file']);
            $param[] = [
                'name' => 'video',
                'contents' => fopen($videoFilePath, 'r'),
            ];
        } elseif (isset($video['buffer'])) {
            $param[] = [
                'name' => 'video',
                'contents' => fopen($video['buffer'], 'r'),
            ];
        } else {
            throw new InvalidArgumentException('param video is illegal');
        }
        if ($compareFlag) {
            if (!is_array($card)) {
                throw new InvalidArgumentException('param card must be array');
            }
            if (isset($card['file'])) {
                $cardFilePath = $this->getFileRealPath($card['file']);
                $param[] = [
                    'name' => 'video',
                    'contents' => fopen($cardFilePath, 'r'),
                ];
            } elseif (isset($card['buffer'])) {
                $param[] = [
                    'name' => 'video',
                    'contents' => $card['buffer'],
                ];
            } else {
                throw new InvalidArgumentException('param card is illegal');
            }
            $files['compare_flag'] = 'true';
            $param[] = [
                'name' => 'compare_flag',
                'contents' => 'true',
            ];
        } else {
            $param[] = [
                'name' => 'compare_flag',
                'contents' => 'false',
            ];
        }
        if ($seq) {
            $param[] = [
                'name' => 'seq',
                'contents' => $seq,
            ];
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 活体检测第二步：检测--身份信息核验.
     *
     * @param string             $validate     faceLiveGetFour获取的验证码
     * @param array(associative) $video        拍摄的视频
     *                                         file   string: 指定图片的路径
     *                                         buffer string: 指定图片的内容
     *                                         以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param string             $idcardNumber 身份证号
     * @param string             $idcardName   姓名
     * @param string             $seq          指定一个sessionId，若使用，请确保id唯一
     *
     * @return array http请求响应
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function faceIdCardLiveDetectFour($validate, $video, $idcardNumber, $idcardName, $seq = null)
    {
        if (!is_array($video)) {
            throw new InvalidArgumentException('param video must be array');
        }
        $reqUrl = $this->conf->buildUrl('/face/idcardlivedetectfour');
        $param = $this->baseMultiParams();
        $param[] = [
            'name' => 'validate_data',
            'contents' => $validate,
        ];
        $param[] = [
            'name' => 'idcard_number',
            'contents' => $idcardNumber,
        ];
        $param[] = [
            'name' => 'idcard_name',
            'contents' => $idcardName,
        ];

        if (isset($video['file'])) {
            $filePath = $this->getFileRealPath($video['file']);
            $param[] = [
                'name' => 'video',
                'contents' => fopen($filePath, 'r'),
            ];
        } elseif (isset($video['buffer'])) {
            $param[] = [
                'name' => 'video',
                'contents' => $video['buffer'],
            ];
        } else {
            throw new InvalidArgumentException('param video is illegal');
        }
        if ($seq) {
            $param[] = [
                'name' => 'seq',
                'contents' => $seq,
            ];
        }

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    // 人脸核身 模块结束 //

    private function baseHeaders()
    {
        return [
            'Authorization' => $this->auth->getSign(),
            // 'User-Agent' => Conf::getUa($this->auth->getAppId()),
        ];
    }

    private function baseJsonParams()
    {
        return [
            'appid' => $this->auth->getAppId(),
            //'bucket' => $this->bucket,
        ];
    }

    private function baseMultiParams()
    {
        return [
            [
                'name' => 'appid',
                'contents' => $this->auth->getAppId(),
            ],
            /*[
                'name' => 'bucket',
                'contents' => $this->bucket,
            ]*/
        ];
    }

    /**
     * 获取文件资源路径.
     *
     * @param $file
     *
     * @return bool|string
     *
     * @throws InvalidFilePathException
     */
    private function getFileRealPath($file)
    {
        if (PATH_SEPARATOR == ';') {    // WIN OS
            $path = iconv('UTF-8', 'gb2312//IGNORE', $file);
        } else {
            $path = $file;
        }
        $filePath = realpath($path);

        if (!file_exists($filePath)) {
            throw new InvalidFilePathException('file '.$file.' not exist');
        }

        return $filePath;
    }

    /**
     * 发送 multiple/form-data 请求
     *
     * @param $reqUrl
     * @param array $data
     *
     * @return mixed
     *
     * @throws HttpException
     */
    protected function sendMultipleFormDataRequest($reqUrl, $data)
    {
        $options = $this->baseRequestOptions();
        $options['multipart'] = $data;

        try {
            $response = $this->getHttpClient()->post($reqUrl, $options)->getBody()->getContents();

            return \GuzzleHttp\json_decode($response, true);
        } catch (ClientException $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 发送 Json 请求
     *
     * @param $reqUrl
     * @param $data
     *
     * @return mixed
     *
     * @throws HttpException
     */
    protected function sendJsonRequest($reqUrl, $data)
    {
        $options = $this->baseRequestOptions();
        $options['json'] = $data;

        try {
            $response = $this->getHttpClient()->post($reqUrl, $options)->getBody()->getContents();

            return \GuzzleHttp\json_decode($response, true);
        } catch (ClientException $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 基本请求头.
     *
     * @return array
     */
    protected function baseRequestOptions()
    {
        return [
            'headers' => $this->baseHeaders(),
            'timeout' => $this->conf->timeout(),
        ];
    }

    /**
     * 识别单张图片.
     *
     * @param array(associative) $picture 识别的图片
     *                                    urls    array: 指定图片的url数组
     *                                    files   array: 指定图片的路径数组
     *                                    buffers array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param string             $reqUrl  请求地址
     * @param array              $options 额外携带的参数
     *
     * @return mixed
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    protected function singlePictureDetect($picture, $reqUrl, $options = [])
    {
        if (isset($picture['url'])) {
            $param = $this->baseJsonParams();
            $param['url'] = $picture['url'];
            if ($options) {
                foreach ($options as $name => $option) {
                    $param[$name] = $option;
                }
            }

            return $this->sendJsonRequest($reqUrl, $param);
        }

        if (isset($picture['file'])) {
            $filePath = $this->getFileRealPath($picture['file']);
            $filename = pathinfo($filePath, PATHINFO_BASENAME);
            $files = [
                'name' => 'image',
                'filename' => $filename,
                'contents' => fopen($filePath, 'r'),
            ];
        } elseif (isset($picture['buffer'])) {
            $files = [
                'name' => 'image',
                'contents' => $picture['buffer'],
            ];
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        $param = $this->baseMultiParams();
        if ($options) {
            foreach ($options as $name => $option) {
                if (!is_array($option)) {
                    $param[] = [
                        'name' => $name,
                        'contents' => $option,
                    ];
                } else {
                    $index = 0;
                    foreach ($option as $item) {
                        $param[] = [
                            'name' => $name."[$index]",
                            'contents' => $item,
                        ];
                        ++$index;
                    }
                }
            }
        }
        $param[] = $files;

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }
}
