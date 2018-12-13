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

class FaceRecognition extends BaseModuleApi
{
    /**
     * 检测图中的人脸（人脸检测）.
     *
     * @param array(associative) $picture 人脸图片
     *                                    url    string: 指定图片的url
     *                                    file   string: 指定图片的路径
     *                                    buffer string: 指定图片的内容
     *                                    以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param int                $mode    检测模式，0为检测所有人脸，1为检测最大的人脸
     * @return \Psr\Http\Message\ResponseInterface
     * 
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceDetect(array $picture, $mode = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $mode && 1 !== $mode) {
            throw new InvalidArgumentException('param mode error');
        }

        $reqUrl = $this->image->buildUrl('/face/detect');
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceShape($picture, $mode = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if (0 !== $mode && 1 !== $mode) {
            throw new InvalidArgumentException('param mode error');
        }
        $reqUrl = $this->image->buildUrl('/face/shape');
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceCompare($pictureA, $pictureB)
    {
        if (!$pictureA || !is_array($pictureA)) {
            throw new InvalidArgumentException('param pictureA must be array');
        }
        if (!$pictureB || !is_array($pictureB)) {
            throw new InvalidArgumentException('param pictureB must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/compare');
        $headers = $this->getHeaders();
        if (isset($pictureA['url'])) {
            $param = $this->baseJsonParams();
            $param['urlA'] = $pictureA['url'];

            if (isset($pictureB['url'])) {
                $param['urlB'] = $pictureB['url'];
            } else {
                throw new InvalidArgumentException('param pictureB is illegal');
            }

            return $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }

    /**
     *创建Person.
     *
     * @param string $personId 创建的Person的ID
     * @param array $groupIds 创建的Person需要加入的Group
     * @param array(associative) $picture    创建的Person的人脸图片
     *                                       url    string: 指定图片的url
     *                                       file   string: 指定图片的路径
     *                                       buffer string: 指定图片的内容
     *                                       以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer
     * @param string $personName 创建的Person的名字
     * @param string $tag 为创建的Person打标签
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceNewPerson($personId, $groupIds, $picture, $personName = null, $tag = null)
    {
        if (!$picture || !is_array($groupIds)) {
            throw new InvalidArgumentException('param groupIds must be array');
        }
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/newperson');

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
     * @return \Psr\Http\Message\ResponseInterface http请求响应
     *
     */
    public function faceDelPerson($personId)
    {
        $reqUrl = $this->image->buildUrl('/face/delperson');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->jsonRequest($reqUrl, $headers, $param);
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
     * @return \Psr\Http\Message\ResponseInterface
     * 
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceAddFace($personId, $pictures, $tag = null)
    {
        if (!$pictures || !is_array($pictures)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/addface');
        $headers = $this->getHeaders();
        
        if (isset($pictures['urls']) && is_array($pictures['urls'])) {
            $param = $this->baseJsonParams();
            $param['person_id'] = $personId;
            if ($tag) {
                $param['tag'] = $tag;
            }
            $param['urls'] = $pictures['urls'];

            $this->jsonRequest($reqUrl, $headers, $param);
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

        return $this->multipleFormDataRequest($reqUrl, $headers, $param);
    }

    /**
     * 删除face.
     *
     * @param string $personId 操作的Person的ID
     * @param array  $faceIds  删除的face的ID数组
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     */
    public function faceDelFace($personId, $faceIds)
    {
        if (!is_array($faceIds)) {
            throw new InvalidArgumentException('param faceIds must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/delface');
        $headers = $this->getHeaders();

        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['face_ids'] = $faceIds;

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 设置信息（名字、标签）.
     *
     * @param string $personId   操作的Person的ID
     * @param string $personName Person的名字
     * @param string $tag        为Person打标签
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceSetInfo($personId, $personName = null, $tag = null)
    {
        $reqUrl = $this->image->buildUrl('/face/setinfo');
        $headers = $this->getHeaders();

        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        if ($personName) {
            $param['person_name'] = strval($personName);
        }
        if ($tag) {
            $param['tag'] = $tag;
        }

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 获取信息.
     *
     * @param string $personId 操作的Person的ID
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceGetInfo($personId)
    {
        $reqUrl = $this->image->buildUrl('/face/getinfo');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 获取appid下的所有组列表.
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceGetGroupIds()
    {
        $reqUrl = $this->image->buildUrl('/face/getgroupids');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 获取group下的所有person列表.
     *
     * @param string $groupId 操作的GroupID
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceGetPersonIds($groupId)
    {
        $reqUrl = $this->image->buildUrl('/face/getpersonids');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['group_id'] = $groupId;

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 获取person的face列表.
     *
     * @param string $personId 操作的Person的ID
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceGetFaceIds($personId)
    {
        $reqUrl = $this->image->buildUrl('/face/getfaceids');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 获取face的信息.
     *
     * @param string $faceId 操作的FaceID
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function faceGetFaceInfo($faceId)
    {
        $reqUrl = $this->image->buildUrl('/face/getfaceinfo');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['face_id'] = $faceId;

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 为 person 新增 group_id.
     *
     * @param string $personId  创建的Person的ID
     * @param array  $groupIds  要新增的 group_ids
     * @param string $sessionId 会话 ID
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     */
    public function faceAddGroupIds($personId, $groupIds, $sessionId = null)
    {
        if (!is_array($groupIds)) {
            throw new InvalidArgumentException('param groupids must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/addgroupids');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['group_ids'] = $groupIds;
        if ($sessionId) {
            $param['session_id'] = $sessionId;
        }

        return $this->jsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 为 person 删除 group_id.
     *
     * @param string $personId  人脸 ID
     * @param array  $groupIds  群组 Id
     * @param string $sessionId 会话ID
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     */
    public function faceDelGroupIds($personId, $groupIds, $sessionId = null)
    {
        if (!is_array($groupIds)) {
            throw new InvalidArgumentException('param groupids must be array');
        }
        $reqUrl = $this->image->buildUrl('face/delgroupids');
        $headers = $this->getHeaders();
        $param = $this->baseJsonParams();
        $param['person_id'] = $personId;
        $param['group_ids'] = $groupIds;
        if ($sessionId) {
            $param['session_id'] = $sessionId;
        }

        return $this->jsonRequest($reqUrl, $headers, $param);
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceVerify($personId, $picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/verify');
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function faceIdentify($groupIds, $picture)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/identify');
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws InvalidArgumentException
     * @throws \Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException
     */
    public function multidentify($picture, $groupIds)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->image->buildUrl('/face/multidentify');

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
}