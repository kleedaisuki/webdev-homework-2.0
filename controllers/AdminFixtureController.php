<?php

/**
 * 用途：W11 后台赛事管理控制器，提供演示比赛的列表、新建、编辑与发布动作。
 *       正式样本在这里只读，任何写入尝试都返回 403。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\controllers;

use app\models\Fixture;
use app\models\FixtureForm;
use app\models\NationalTeam;
use app\models\Tournament;
use app\models\Venue;
use Yii;
use yii\data\Pagination;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class AdminFixtureController extends AdminBaseController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'create' => ['GET', 'POST'],
                    'update' => ['GET', 'POST'],
                    'publish' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * 演示与正式样本一起列出，演示行可以进入编辑。
     */
    public function actionIndex(): string
    {
        $query = Fixture::find()
            ->with(['tournament', 'venue', 'homeTeam', 'awayTeam'])
            ->orderBy(['kickoff_at' => SORT_DESC, 'id' => SORT_DESC]);

        $pagination = new Pagination([
            'totalCount' => (clone $query)->count(),
            'pageSize' => Fixture::PAGE_SIZE,
            'defaultPageSize' => Fixture::PAGE_SIZE,
        ]);

        $fixtures = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'fixtures' => $fixtures,
            'pagination' => $pagination,
        ]);
    }

    public function actionCreate()
    {
        $model = new FixtureForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', '演示比赛已保存为草稿。');
            return $this->redirect(['update', 'id' => $model->getFixture()->id]);
        }

        return $this->render('create', $this->formData($model));
    }

    public function actionUpdate($id)
    {
        $fixture = $this->findDemoFixture($id);

        $model = new FixtureForm();
        $model->loadFixture($fixture);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', '修改已保存。');
            return $this->redirect(['update', 'id' => $fixture->id]);
        }

        return $this->render('update', $this->formData($model) + ['fixture' => $fixture]);
    }

    /**
     * 发布是独立的 POST 动作，与保存分开。
     */
    public function actionPublish($id)
    {
        $fixture = $this->findDemoFixture($id);

        $model = new FixtureForm();
        $model->loadFixture($fixture);

        if ($model->publish()) {
            Yii::$app->session->setFlash('success', '已发布到前台。');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }

        return $this->redirect(['update', 'id' => $fixture->id]);
    }

    /**
     * 删除演示记录；正式样本返回 403。
     */
    public function actionDelete($id)
    {
        $fixture = $this->findDemoFixture($id);
        $fixture->delete();
        Yii::$app->session->setFlash('success', '演示比赛已删除。');

        return $this->redirect(['index']);
    }

    /**
     * 只允许维护演示记录；正式样本、不存在的记录分别返回 403 与 404。
     */
    private function findDemoFixture($id): Fixture
    {
        $fixture = Fixture::findOne((int) $id);

        if ($fixture === null) {
            throw new NotFoundHttpException('比赛不存在。');
        }
        if (!$fixture->isDemo()) {
            throw new ForbiddenHttpException('正式样本只读。');
        }

        return $fixture;
    }

    private function formData(FixtureForm $model): array
    {
        $tournaments = Tournament::find()
            ->orderBy(['edition' => SORT_DESC])
            ->all();
        $teams = NationalTeam::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $venues = Venue::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return [
            'model' => $model,
            'tournaments' => \yii\helpers\ArrayHelper::map($tournaments, 'id', static function (Tournament $tournament) {
                return $tournament->name;
            }),
            'teams' => \yii\helpers\ArrayHelper::map($teams, 'id', static function (NationalTeam $team) {
                return $team->code . ' ' . $team->name;
            }),
            'venues' => \yii\helpers\ArrayHelper::map($venues, 'id', static function (Venue $venue) {
                return $venue->name . ' · ' . $venue->city;
            }),
        ];
    }
}
