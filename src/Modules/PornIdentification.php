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

class PornIdentification extends BaseModuleApi
{
    /**
     * 黄图识别.
     *
     * @param array(associative) $picture 识别的图片
     *                                    urls    array: 指定图片的url数组
     *                                    files   array: 指定图片的路径数组
     *                                    以上两种指定其一即可，如果指定多个，则优先使用urls，其次 files
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function pornDetect($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/detection/pornDetect');
        $headers = $this->getHeaders();

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }
}