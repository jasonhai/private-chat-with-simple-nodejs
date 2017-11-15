<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Message;
use app\components\CmsFormatter;

/* @var $this yii\web\View */

$this->title = 'Chat - Gogtour';

$js = <<<JS
$('#chat-form').submit(function() {

     var form = $(this);

     $.ajax({
          url: form.attr('action'),
          type: 'post',
          data: form.serialize(),
          success: function (response) {
               $("#message-field").val("");
          }
     });

     return false;
});
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>

<div class="hfeed site " id="page">
    <section class="main chat-page gog-chat-custom tours">
        <div class="wrapper">
            <div class="clearfix col-md-12">
                <div class="chatLeft col-md-5">
                    <div class="col-md-12 main-title">Message</div>
                    <?php if (count($chatlogMenu)): ?>
                        <div class="srcoll-out">
                            <div class="scroll-in">
                                <ul class="chatLeftList">
                                    <?php
                                    foreach ($chatlogMenu as $key => $menu):
                                        if ($menu->userTo->id == Yii::$app->user->id) {
                                            $u = $menu->userFrom;
                                        } else {
                                            $u = $menu->userTo;
                                        }
                                        if (isset($u)) {
                                            $userUrl = Yii::$app->urlManager->createUrl(['site/chat', 'id' => $u->id]);
                                            $time = date('D g:ia', strtotime($menu->created_date));
                                            ?>
                                            <li class="conversation-left" id="conversation-<?= $menu->id ?>">
                                                <div class="chatLeftBox">
                                                    <div class="chatLeftBoxTtl clearfix">
                                                        <a class="text-one-line user-fullname" href="<?= $userUrl ?>">
                                                            <?= $u->full_name ?>
                                                        </a>
                                                        <span class='message-time'><?= $time ?></span>
                                                    </div>
                                                    <div class="chatLeftBoxCnt">
                                                        <a class="chat-history text-one-line"
                                                           href="<?= $userUrl ?>"><?= Message::getLastMessage($menu) ?></a>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    <?php endforeach ?>

                                </ul>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
                <div class="chatRight col-md-7">
                    <?php if (empty($channel)): ?>
                        <div class="col-md-12 main-title">Please choose one person to begin your chat</div>
                    <?php else: ?>
                        <div class="col-md-12 main-title"><?= $userToModel->full_name ?></div>
                        <?= Html::beginForm(['/site/chat'], 'POST', [
                        'id' => 'chat-form'
                    ]) ?>
                        <input type="hidden" name="conversationId" value="<?= $conversationId ?>"/>
                        <input type="hidden" name="channel" value="<?= $channel ?>"/>
                        <input type="hidden" name="from" value="<?= $user_id_from ?>"/>

                        <ul class="chatRList">
                            <?php if (count($chatlog)): ?>
                                <?php
                                foreach ($chatlog as $key => $chat):
                                    $userUrl = Yii::$app->urlManager->createUrl(['site/viewprofile', 'id' => $chat->conversation->userFrom->id]);
                                    $urlImage = 'https://gogtour.com/upload/users/38/100x100/1496997657_38_1 SOPHIA ANNA.jpg';
                                    ?>
                                    <li class="clearfix <?= ($chat->userFrom->id == $user_id_from) ? 'we-chat we-chat-me' : 'we-chat we-chat-you' ?>">
                                        <?php if ($chat->userFrom->id != $user_id_from): ?>
                                            <p class="avatar"><a href="<?= $userUrl ?>" target="_blank"><img
                                                            src="<?= $urlImage ?>"
                                                            alt=""/></a></p>
                                        <?php endif; ?>
                                        <div class="block-chat">
                                            <div class="chatRBox"><?= $chat->message ?></div>
                                        </div>
                                    </li>
                                <?php endforeach ?>
                            <?php endif ?>
                        </ul>
                        <div class="chatRArea">
                            <?= Html::textArea('message', null, [
                                'id' => 'message-field',
                                'class' => 'form-control',
                                'placeholder' => 'Say something...',
                                'required' => 'required',
                                'rows' => 4
                            ]) ?>
                            <div class="chatRSend">
                                <?= Html::submitButton('Send', ['class' => 'btn-search']) ?>
                            </div>
                        </div>
                        <?= Html::endForm() ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    $(document).ready(function () {
        $(".chatRList").animate({scrollTop: $('.chatRList').prop("scrollHeight")}, 100);
    });
</script>