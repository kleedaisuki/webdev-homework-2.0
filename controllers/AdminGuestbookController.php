<?php

namespace app\controllers;

use app\models\GuestMessage;
use app\models\GuestReview;
use app\models\ReviewForm;
use Yii;
use yii\data\Pagination;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class AdminGuestbookController extends AdminBaseController
{
    private const STATUS_LABELS = [
        GuestMessage::STATUS_PENDING => '待审核',
        GuestMessage::STATUS_APPROVED => '已通过',
        GuestMessage::STATUS_REJECTED => '已拒绝',
    ];

    public function behaviors(): array
    {
        return parent::behaviors() + ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET'], 'review' => ['POST']],
        ]];
    }

    public function actionIndex(): string
    {
        $status = Yii::$app->request->get('status');
        if (!is_string($status) || !isset(self::STATUS_LABELS[$status])) {
            $status = GuestMessage::STATUS_PENDING;
        }

        $query = GuestMessage::find()->where(['status' => $status])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
        $pagination = new Pagination([
            'totalCount' => (int) $query->count(),
            'defaultPageSize' => 20,
            'pageSizeParam' => false,
        ]);

        return $this->render('index', [
            'statuses' => self::STATUS_LABELS,
            'status' => $status,
            'messages' => $query->offset($pagination->offset)->limit($pagination->limit)->asArray()->all(),
            'pagination' => $pagination,
        ]);
    }

    public function actionReview(string $id): string|Response
    {
        $form = new ReviewForm();
        $form->load(Yii::$app->request->post());
        if (!$form->validate()) {
            $message = GuestMessage::find()->where(['id' => $id])->asArray()->one();
            if ($message === null) {
                throw new NotFoundHttpException('留言不存在。');
            }

            return $this->render('retry', ['message' => $message, 'form' => $form]);
        }

        $changed = GuestReview::record($id, Yii::$app->user->id, $form->decision,
            $form->note === '' ? null : $form->note);
        if ($changed === null) {
            throw new NotFoundHttpException('留言不存在。');
        }
        Yii::$app->session->setFlash('guestReview', $changed
            ? '审核结果：' . self::STATUS_LABELS[$form->decision] . '。'
            : '该留言已是此状态，无需重复审核。');

        return $this->redirect(['index'], 303);
    }
}
