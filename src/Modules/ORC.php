<?php

/*
 * This file is part of the jasongzj/laravel-qcloud-image.
 *
 * (c) jasongzj <jasongzj@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jasongzj\LaravelQcloudImage\Modules;

use Jasongzj\LaravelQcloudImage\Exceptions\InvalidArgumentException;

class ORC extends BaseModuleApi
{
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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function idcardDetect($picture, $cardType = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        if (0 !== $cardType && 1 !== $cardType) {
            throw new InvalidArgumentException('param cardType error');
        }

        $reqUrl = $this->image->buildUrl('/ocr/idcard');
        $headers = $this->getHeaders();
        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['card_type'] = $cardType;
            $param['url_list'] = $picture['urls'];

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function namecardV2Detect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->image->buildUrl('/ocr/businesscard');
        $headers = $this->getHeaders();
        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
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
     * @return mixed|\Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function drivingLicence($picture, $type = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $type && 1 !== $type) {
            throw new InvalidArgumentException('param type error');
        }
        $reqUrl = $this->image->buildUrl('/ocr/drivinglicence');

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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function plate($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->image->buildUrl('/ocr/plate');

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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function bankcard($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/ocr/bankcard');

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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function bizlicense($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/ocr/bizlicense');

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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function general($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/ocr/general');

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
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function handwriting($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/ocr/handwriting');

        return $this->singlePictureDetect($picture, $reqUrl);
    }
}
