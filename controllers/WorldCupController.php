<?php
/**
 * 用途：世界杯赛程与赛果浏览控制器 WorldCupController（W07）。
 * 作者：孙锘梵（2410868，成员3）
 */

namespace app\controllers;

use app\models\Fixture;
use app\models\Tournament;
use Yii;
use yii\data\Pagination;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * 公开页面：/world-cup/matches 列表与 /world-cup/matches/{id} 详情。
 *
 * 只有已发布（publication_status = published）且属于本期届次的比赛可见；
 * 草稿、其他届次与不存在的比赛统一返回 404，不暴露存在性。
 */
class WorldCupController extends Controller
{
    /**
     * 赛程赛果列表，支持按比赛状态筛选与分页。
     *
     * @param string|null $status scheduled / finished，缺省为全部
     * @param int|null $page 页码，由 Pagination 读取
     */
    public function actionIndex($status = null, $page = null)
    {
        $status = $status === '' ? null : $status;
        if ($status !== null && !in_array($status, [Fixture::STATUS_SCHEDULED, Fixture::STATUS_FINISHED], true)) {
            throw new BadRequestHttpException('未知的比赛状态筛选，只能是 scheduled 或 finished。');
        }

        $query = Fixture::findPublished()
            ->alias('f')
            ->innerJoin(['t' => Tournament::tableName()], 't.id = f.tournament_id')
            ->andWhere(['t.edition' => Tournament::DEFAULT_EDITION])
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy(['f.kickoff_at' => SORT_ASC, 'f.id' => SORT_ASC]);

        if ($status !== null) {
            $query->andWhere(['f.match_status' => $status]);
        }

        $pagination = new Pagination([
            'totalCount' => $query->count(),
            'pageSize' => Fixture::PAGE_SIZE,
            'pageParam' => 'page',
            'pageSizeParam' => false,
            'validatePage' => true,
        ]);

        $fixtures = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $this->view->title = '赛程赛果';

        return $this->render('index', [
            'fixtures' => $fixtures,
            'pagination' => $pagination,
            'status' => $status,
        ]);
    }

    /**
     * 单场比赛详情；草稿、非本期届次与不存在统一 404。
     *
     * @param int $id 比赛 ID
     */
    public function actionView($id)
    {
        $fixture = Fixture::findPublished()
            ->alias('f')
            ->innerJoin(['t' => Tournament::tableName()], 't.id = f.tournament_id')
            ->andWhere(['t.edition' => Tournament::DEFAULT_EDITION, 'f.id' => (int) $id])
            ->with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->one();

        if ($fixture === null) {
            throw new NotFoundHttpException('没有找到这场比赛。');
        }

        $this->view->title = $fixture->homeTeam->name . ' vs ' . $fixture->awayTeam->name;

        return $this->render('view', [
            'fixture' => $fixture,
        ]);
    }
}
