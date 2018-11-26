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
     *                  url    string: 指定图片的url
     *                  file   string: 指定图片的路径
     *                  buffer string: 指定图片的内容
     *                  以上三种指定其一即可，如果指定多个，则优先使用url，其次 file，再次 buffer。
     * @param  int  $mode  检测模式，0为检测所有人脸，1为检测最大的人脸
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
        $headers = $this->baseHeaders();

        if (isset($picture['url'])) {
            $param = $this->baseJsonParams();
            $param['mode'] = $mode;
            $param['url'] = $picture['url'];

            try {
                $response = $this->getHttpClient()->post($reqUrl, [
                    'headers' => $headers,
                    'timeout' => $this->conf->timeout(),
                    'json' => $param,
                ])->getBody()->getContents();
                return \GuzzleHttp\json_decode($response, true);
            } catch (\Exception $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }

        }

        if (isset($picture['file'])) {
            if (PATH_SEPARATOR == ';') {    // WIN OS
                $path = iconv("UTF-8", "gb2312//IGNORE", $picture['file']);
            } else {
                $path = $picture['file'];
            }
            $filePath = realpath($path);

            if (!file_exists($filePath)) {
                throw new InvalidFilePathException('file ' . $picture['file'] . ' not exist');
            }

            $files = [
                'name' => 'image',
                'contents' => fopen($filePath, 'r')
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
        $mode = [
            'name' => 'mode',
            'contents' => $mode,
        ];

        array_push($param, $mode);
        array_push($param, $files);

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
    }

    /**
     * 黄图识别
     * @param  array(associative) $picture   识别的图片
     *                 * * @param  array(associative) $pictures   Person的人脸图片
     *                  urls    array: 指定图片的url数组
     *                  files   array: 指定图片的路径数组
     *                  以上两种指定其一即可，如果指定多个，则优先使用urls，其次 files
     *
     * @return array    http请求响应
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws InvalidFilePathException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pornDetect($picture) {

        if (!$picture || !is_array($picture)) {
            throw new InvalidArgumentException('param picture must be array');
        }
        $reqUrl = $this->conf->buildUrl('/detection/pornDetect');
        $headers = $this->baseHeaders();

        if (isset($picture['urls'])) {
            $headers[] = 'Content-Type:application/json';
            $param = $this->baseJsonParams();
            $param['url_list'] = $picture['urls'];

            try {
                $response = $this->getHttpClient()->post($reqUrl, [
                    'headers' => $headers,
                    'timeout' => $this->conf->timeout(),
                    'json' => $param,
                ])->getBody()->getContents();
                return \GuzzleHttp\json_decode($response, true);
            } catch (\Exception $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else if (isset($picture['files'])){
            $param = $this->baseMultiParams();
            $index = 0;

            foreach ($picture['files'] as $file) {
                if (PATH_SEPARATOR == ';') {    // WIN OS
                    $path = iconv("UTF-8", "gb2312//IGNORE", $file);
                } else {
                    $path = $file;
                }
                $filePath = realpath($path);

                if (!file_exists($filePath)) {
                    throw new InvalidFilePathException('file ' . $file . ' not exist');
                }

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
}