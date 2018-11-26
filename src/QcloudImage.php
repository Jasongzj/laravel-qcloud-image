<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 2018/11/22
 * Time: 0:09
 */

namespace Jasongzj\LaravelQcloudImage;


use GuzzleHttp\Client;
use Jasongzj\LaravelQcloudImage\Exceptions\HttpException;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidArgumentException;
use Jasongzj\LaravelQcloudImage\Exceptions\InvalidFilePathException;

class QcloudImage
{
    protected $auth;
    protected $bucket;
    protected $conf;
    protected $guzzleOptions = [];

    public function __construct($appId, $secretId, $secretKey, $bucket)
    {
        $this->bucket = $bucket;
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
     * 那么: 请继续使用旧域名
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
     * 那么: 请继续使用旧域名
     */
    public function useOldDomain()
    {
        $this->conf->useOldDomain();
    }

    /**
     * 检测图中的人脸
     * @param  array(associative) $picture   人脸图片
     *         url    string: 指定图片的url
     *         file   string: 指定图片的路径
     *         buffer string: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  int $mode 检测模式，0为检测所有人脸，1为检测最大的人脸
     * @return string
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function faceDetect(array $picture, $mode = 0)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if ($mode !== 0 && $mode !== 1) {
            throw new InvalidArgumentException('param mode error');
        }

        $reqUrl = $this->conf->buildUrl('/face/detect');
        $options = ['mode' => $mode];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 黄图识别
     * @param  array(associative) $picture   识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         以上两种指定其一即可，如果指定多个，则优先使用urls，其次 files
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pornDetect($picture)
    {

        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/detection/pornDetect');
        $headers = $this->baseHeaders();

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->sendJsonRequest($reqUrl, $headers, $param);
        } else if (isset($picture['files'])) {
            $param = $this->baseMultiParams();
            $index = 0;

            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $data = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r')
                ];
                array_push($param, $data);
                $index++;
            }

            try {
                $response = $this->getHttpClient()->request('POST', $reqUrl, [
                    'headers' => $headers,
                    'timeout' => $this->conf->timeout(),
                    'multipart' => $param
                ])->getBody()->getContents();
                return \GuzzleHttp\json_decode($response, true);
            } catch (\Exception $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }
    }

    /**
     * 标签识别
     * @param  array(associative) $picture   识别的图片
     *         url    array: 指定图片的url数组
     *         file   array: 指定图片的路径数组
     *         buffer string: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @return array    http请求响应
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

        } else if (isset($picture['buffer'])) {
            $files = [
                'name' => 'image',
                'contents' => base64_encode($picture['buffer'])
            ];

        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        $param = $this->baseMultiParams();
        array_push($param, $files);

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }

    /**
     * 身份证识别
     * @param  array(associative) $picture   识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param  int $cardType 0为身份证有照片的一面，1为身份证有国徽的一面
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function idcardDetect($picture, $cardType = 0)
    {
        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        if ($cardType !== 0 && $cardType !== 1) {
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
            'contents' => $cardType
        ];
        if (isset($picture['files'])) {
            $index = 0;
            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $data = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r')
                ];
                array_push($param, $data);
                $index++;
            }

        } else if (isset($picture['buffers'])) {
            $index = 0;
            foreach ($picture['buffers'] as $buffer) {
                $data = [
                    'name' => "image[$index]",
                    'contents' => $buffer
                ];
                array_push($param, $data);
                $index++;
            }

        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        return $this->sendJsonRequest($reqUrl, $param);
    }

    /**
     * 名片识别v2
     * @param  array(associative) $picture   识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
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
        $headers = $this->baseHeaders();

        if (isset($picture['urls'])) {
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            return $this->sendJsonRequest($reqUrl, $headers, $param);
        }

        $param = $this->baseMultiParams();

        if (isset($picture['files'])) {
            $index = 0;
            foreach ($picture['files'] as $file) {
                $filePath = $this->getFileRealPath($file);
                $filename = pathinfo($filePath, PATHINFO_BASENAME);
                $data = [
                    'name' => "image[$index]",
                    'filename' => $filename,
                    'contents' => fopen($filePath, 'r')
                ];
                array_push($param, $data);
                $index++;
            }

        } else if (isset($picture['buffers'])) {
            $index = 0;
            foreach ($picture['buffers'] as $buffer) {
                $data = [
                    'name' => "image[$index]",
                    'contents' => $buffer
                ];
                array_push($param, $data);
                $index++;
            }

        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        return $this->sendJsonRequest($reqUrl, $headers, $param);
    }

    /**
     * 行驶证驾驶证识别
     * @param  array(associative) $picture   识别的图片
     *        urls    array: 指定图片的url数组
     *        files   array: 指定图片的路径数组
     *        buffers array: 指定图片的内容
     *        以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param int $type 表示识别类型，0 表示行驶证，1 表示驾驶证，2 表示行驶证副页。
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function drivingLicence($picture, $type = 0)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        if ($type !== 0 && $type !== 1) {
            throw new InvalidArgumentException('param type error');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/drivinglicence');

        $options = ['type' => $type];

        return $this->singlePictureDetect($picture, $reqUrl, $options);
    }

    /**
     * 车牌号识别
     * @param  array(associative) $picture   车牌号的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function plate($picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }

        $reqUrl = $this->conf->buildUrl('/ocr/plate');
        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 银行卡识别
     * @param  array(associative) $picture   银行卡的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function bankcard($picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/bankcard');
        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 营业执照识别
     * @param  array(associative) $picture   营业执照图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function bizlicense($picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/bizlicense');
        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 通用印刷体识别
     * @param  array(associative) $picture   识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function general($picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/general');

        return $this->singlePictureDetect($picture, $reqUrl);
    }

    /**
     * 手写体识别
     * @param  array(associative) $picture   识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     */
    public function handwriting($picture)
    {
        if (!is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/ocr/handwriting');

        return $this->singlePictureDetect($picture, $reqUrl);
    }


    private function baseHeaders()
    {
        return [
            'Authorization' => $this->auth->getSign($this->bucket),
            'User-Agent' => Conf::getUa($this->auth->getAppId()),
        ];
    }

    private function baseJsonParams()
    {
        return [
            'appid' => $this->auth->getAppId(),
            'bucket' => $this->bucket,
        ];
    }

    private function baseMultiParams()
    {
        return [
            [
                'name' => 'appid',
                'contents' => $this->auth->getAppId(),
            ],
            [
                'name' => 'bucket',
                'contents' => $this->bucket,
            ]
        ];
    }

    /**
     * 获取文件资源路径
     * @param $file
     * @return bool|string
     * @throws InvalidFilePathException
     */
    private function getFileRealPath($file)
    {
        if (PATH_SEPARATOR == ';') {    // WIN OS
            $path = iconv("UTF-8", "gb2312//IGNORE", $file);
        } else {
            $path = $file;
        }
        $filePath = realpath($path);

        if (!file_exists($filePath)) {
            throw new InvalidFilePathException('file ' . $file . ' not exist');
        }
        return $filePath;
    }

    /**
     * 发送 multiple/form-data 请求
     * @param $reqUrl
     * @param array $data
     * @return mixed
     * @throws HttpException
     */
    protected function sendMultipleFormDataRequest($reqUrl, $data)
    {
        $options = $this->baseRequestOptions();
        $options['multipart'] = $data;
        try {
            $response = $this->getHttpClient()->post($reqUrl, $options)->getBody()->getContents();
            return \GuzzleHttp\json_decode($response, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 发送 Json 请求
     * @param $reqUrl
     * @param $data
     * @return mixed
     * @throws HttpException
     */
    protected function sendJsonRequest($reqUrl, $data)
    {
        $options = $this->baseRequestOptions();
        $options['json'] = $data;
        try {
            $response = $this->getHttpClient()->post($reqUrl, $options)->getBody()->getContents();
            return \GuzzleHttp\json_decode($response, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 基本请求头
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
     * 识别单张图片
     * @param  array(associative) $picture  识别的图片
     *         urls    array: 指定图片的url数组
     *         files   array: 指定图片的路径数组
     *         buffers array: 指定图片的内容
     *         以上三种指定其一即可，如果指定多个，则优先使用urls，其次 files，最后buffers
     * @param  string $reqUrl 请求地址
     * @param  array $options 额外携带的参数
     * @return mixed
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
            $files = [
                'name' => 'image',
                'contents' => fopen($filePath, 'r'),
            ];

        } else if (isset($picture['buffer'])) {
            $files = [
                'name' => 'image',
                'contents' => $picture['buffer']
            ];
        } else {
            throw new InvalidArgumentException('param picture is illegal');
        }

        $param = $this->baseMultiParams();
        if ($options) {
            foreach ($options as $name => $option) {
                $param[] = [
                    'name' => $name,
                    'contents' => $option,
                ];
            }
        }
        $param[] = $files;

        return $this->sendMultipleFormDataRequest($reqUrl, $param);
    }
}