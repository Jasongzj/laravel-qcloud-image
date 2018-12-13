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


use Jasongzj\LaravelQcloudImage\Core\BaseApi;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidArgumentException;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException;

class BaseModuleApi extends BaseApi
{
    /**
     * @param array(associative) $picture 识别的图片
     *                                    urls    array: 指定图片的url数组
     *                                    files   array: 指定图片的路径数组
     *                                    buffers array: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param string $reqUrl 请求地址
     * @param array $options 额外携带的参数
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    protected function singlePictureDetect($picture, $reqUrl, $options = [])
    {
        $headers = $this->getHeaders();
        if (isset($picture['url'])) {
            $param = $this->baseJsonParams();
            $param['url'] = $picture['url'];
            if ($options) {
                foreach ($options as $name => $option) {
                    $param[$name] = $option;
                }
            }

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => $this->image->getSign(),
        ];
    }

    protected function baseJsonParams()
    {
        return [
            'appid' => $this->image->getAppId(),
        ];
    }

    protected function baseMultiParams()
    {
        return [
            [
                'name' => 'appid',
                'contents' => $this->image->getAppId(),
            ],
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
    protected function getFileRealPath($file)
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
}