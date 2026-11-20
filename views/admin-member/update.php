<?php
/** 用途：课设成员资料与个人公开链接编辑。作者：zhixiu-feng */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = '编辑成员资料';
$this->registerCssFile('@web/css/profile-editor.css');
?>
<section class="profile-editor">
    <div class="editor-breadcrumb"><?= Html::a('管理工作台', ['admin/index']) ?><span>/</span><?= Html::a('团队资料', ['admin-team/update']) ?><span>/</span><span>成员资料</span></div>
    <header class="editor-heading"><div><h1>成员资料</h1><p><?= Html::encode($member->display_name) ?></p></div><?= Html::a('查看公开页面 ↗', ['team/member', 'id' => $member->id], ['class' => 'editor-preview', 'target' => '_blank', 'rel' => 'noopener']) ?></header>
    <?= $this->render('../profile-editor/_feedback', ['editor' => $editor]) ?>
    <?php $form = ActiveForm::begin(['id' => 'member-editor', 'enableClientValidation' => false]); ?>
        <section class="editor-panel"><h2>基本资料</h2>
            <div class="editor-two-columns"><?= $form->field($member, 'display_name')->textInput(['maxlength' => 80])->label('成员姓名') ?><?= $form->field($member, 'role')->textInput(['maxlength' => 100])->label('角色与分工') ?></div>
            <?= $form->field($member, 'bio')->textarea(['rows' => 5, 'maxlength' => 5000])->label('个人简介') ?>
            <?= $form->field($member, 'sort_order')->input('number', ['min' => 0, 'max' => 4294967295])->label('显示顺序') ?>
        </section>
        <?= $this->render('../profile-editor/_links', ['editor' => $editor, 'heading' => '个人公开链接']) ?>
        <div class="editor-actions"><?= Html::submitButton('保存成员资料', ['class' => 'button', 'name' => 'intent', 'value' => 'save']) ?><?= Html::a('返回团队管理', ['admin-team/update'], ['class' => 'text-link']) ?></div>
    <?php ActiveForm::end(); ?>
</section>
