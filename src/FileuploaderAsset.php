<?php
// +----------------------------------------------------------------------
// | FileuploaderAsset
// +----------------------------------------------------------------------
// | User: Lengnuan <25314666@qq.com>
// +----------------------------------------------------------------------
// | Date: 2021年08月31日
// +----------------------------------------------------------------------

namespace lengnuan\fileuploader;

use yii\web\AssetBundle;

class FileuploaderAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@vendor/lengnuan-v/yii2-fileuploader/src/assets';

    /**
     * @var array
     */
    public $css = [
        'css/jquery-filer.css',
        'css/jquery.filer.css',
        'css/jquery.filer-dragdropbox.css',
    ];


    /**
     * @var array
     */
    public $js = [
        'js/jquery.filer.js',
    ];

}