<?php
/**
 * 用途：提供工程临时首页和统一错误页；正式世界杯首页交给 W09。
 * 作者：Clash727（W01）
 */

namespace app\controllers;

use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['GET']],
            ],
        ];
    }

    public function actions(): array
    {
        return ['error' => ['class' => ErrorAction::class]];
    }

    public function actionIndex(): string
    {
        return $this->render('index');
    }
}
