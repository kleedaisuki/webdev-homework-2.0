<?php

use app\models\GuestMessage;
use yii\helpers\Html;

$id = $message['id'];
$noteId = 'review-note-' . $id;
$note = $form?->note;
?>
<div class="review-card">
    <?= $this->render('/shared/_guest-message', ['message' => $message]) ?>
    <?= Html::beginForm(['review', 'id' => $id], 'post', ['class' => 'review-form']) ?>
        <?php if ($form !== null): ?>
            <?= Html::errorSummary($form, ['header' => '', 'class' => 'error-summary', 'role' => 'alert']) ?>
        <?php endif; ?>
        <?= Html::label('审核备注（选填，仅后台留档）', $noteId) ?>
        <?= Html::textarea('ReviewForm[note]', is_string($note) ? $note : '', [
            'id' => $noteId, 'maxlength' => 500, 'rows' => 2,
        ]) ?>
        <div class="review-actions">
            <?php foreach ([GuestMessage::STATUS_APPROVED => '通过', GuestMessage::STATUS_REJECTED => '拒绝'] as $decision => $label): ?>
                <?php if ($message['status'] !== $decision): ?>
                    <?= Html::submitButton(($message['status'] === GuestMessage::STATUS_PENDING ? '' : '改为') . $label, [
                        'name' => 'ReviewForm[decision]', 'value' => $decision,
                        'class' => 'button' . ($decision === GuestMessage::STATUS_REJECTED ? ' review-reject' : ''),
                    ]) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?= Html::endForm() ?>
</div>
