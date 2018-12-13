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

class Tag extends BaseModuleApi
{
    /**
     * 标签识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    url    array: 指定图片的url数组
     *                                    file   array: 指定图片的路径数组
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function tagDetect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->image->buildUrl('/v1/detection/imagetag_detect');
        $headers = $this->getHeaders();

        if (isset($picture['url'])) {
            $param = $this->baseJsonParams();
            $param['url'] = $picture['url'];

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }
}
