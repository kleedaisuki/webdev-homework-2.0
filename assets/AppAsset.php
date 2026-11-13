<?php
/**
 * 用途：注册随项目提供的样式及 Composer 安装的 Yii / jQuery 资源。
 * 作者：Clash727（W01）
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/site.css'];
    public $depends = [YiiAsset::class];
}
