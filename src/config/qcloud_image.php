<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 2018/11/22
 * Time: 15:38
 */

return [
    /**
     * 图像识别基本配置
     */
    'appid' => env('QCLOUD_IMAGE_APPID'),
    'secret_id' => env('QCLOUD_IMAGE_SECRET_ID'),
    'secret_key' => env('QCLOUD_IMAGE_SECRET_KEY'),
    'bucket' => env('QCLOUD_IMAGE_BUCKET', ''),
];