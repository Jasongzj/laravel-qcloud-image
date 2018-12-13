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

class FaceIn extends BaseModuleApi
{
    /**
     * 人脸静态活体检测.
     *
     * @param array(associative) $picture 人脸图片
     *                                    url    string: 指定图片的url
     *                                    file   string: 指定图片的路径
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function liveDetectPicture($picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/livedetectpicture');

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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceIdCardCompare($idcardNumber, $idcardName, $picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/idcardcompare');
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceLiveGetFour($seq = null)
    {
        $reqUrl = $this->image->buildUrl('/face/livegetfour');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        if ($seq) {
            $param['seq'] = strval($seq);
        }

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 活体检测第二步：检测--视频与用户视频比对.
     *
     * @param string $validate faceLiveGetFour获取的验证码
     * @param array(associative) $video       拍摄的视频
     *                                        file   string: 指定图片的路径
     *                                        buffer string: 指定图片的内容
     *                                        以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param bool $compareFlag 是否将视频中的人和card图片比对
     * @param array(associative) $card        人脸图片
     *                                        file   string: 指定图片的路径
     *                                        buffer string: 指定图片的内容
     *                                        以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param string $seq 指定一个sessionId，若使用，请确保id唯一
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceLiveDetectFour($validate, $video, $compareFlag, $card = null, $seq = null)
    {
        if (!is_array($video)) {
            throw new InvalidArgumentException('param video must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/livedetectfour');
        $headers = $this->getHeaders();
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }

    /**
     ** 活体检测第二步：检测--身份信息核验.
     *
     * @param string             $validate     faceLiveGetFour获取的验证码
     * @param array(associative) $video        拍摄的视频
     *                                         file   string: 指定图片的路径
     *                                         buffer string: 指定图片的内容
     *                                         以上二种指定其一即可，如果指定多个，则优先使用 file，其次 buffer
     * @param string             $idcardNumber 身份证号
     * @param string             $idcardName   姓名
     * @param string             $seq          指定一个sessionId，若使用，请确保id唯一
     * @return \Psr\Http\Message\ResponseInterface http请求响应
     *
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceIdCardLiveDetectFour($validate, $video, $idcardNumber, $idcardName, $seq = null)
    {
        if (!is_array($video)) {
            throw new InvalidArgumentException('param video must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/idcardlivedetectfour');
        $headers = $this->getHeaders();
        $param = $this->baseMultiParams();
        $param[] = [
            [
                'name' => 'validate_data',
                'contents' => $validate,
            ],
            [
                'name' => 'idcard_number',
                'contents' => $idcardNumber,
            ],
            [
                'name' => 'idcard_name',
                'contents' => $idcardName,
            ],
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }
}