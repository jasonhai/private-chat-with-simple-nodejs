## Overview
> This is a simple app to guide the people how to create a simple chat with Socket, Nodejs, Express and Yii2 basic

## 1. Run composer self-update
> composer self-update

## 2. Run composer global require fxp/composer-asset-plugin --no-plugins
> composer global require fxp/composer-asset-plugin --no-plugins

## 3. Install Yii 2 basic application template
> php composer.phar create-project yiisoft/yii2-app-basic basic 2.0.6

## 4. Install NodeJS
> Instructions here: https://www.digitalocean.com/community/tutorials/how-to-set-up-a-node-js-application-for-production-on-ubuntu-16-04

## 5. Install Redis
> Instructions here: https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-redis-on-ubuntu-16-04

## 6. Install yii2-redis extension
- php composer.phar require --prefer-dist yiisoft/yii2-redis

- After install configure yii2-redis in your config/web.php config file:
```
return [
    'components' => [
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
    ]
];
```

## 7. Create the NodeJS server
> Create /nodejs folder in your Yii project root. $ cd nodejs into newly created folder and run this commands in your console to install express, socket.io and redis.io:

$ npm install express
$ npm install socket.io
$ npm install redis

After installing create new /nodejs/server.js file with this content:
```
var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);
var redis = require('redis');

var port = '8890';
usernames = [];

var io = require('socket.io');

// Create a Socket.IO instance, passing it our server
var socket = io.listen(server);

server.listen(port, function () {
    console.log('Express server listening on port %d in %s mode', port, app.get('env'));
});

socket.on('connection', function (socket) {
    console.info('New client connected (id=' + socket.id + ').');
    usernames.push(socket.id);

    var redisClient = redis.createClient();
    redisClient.on("message", function(channel, message) {
        console.log("New message: " + message + ". In channel: " + channel);
        console.log('Room: '+socket.room, socket.rooms);

        io.sockets.in(socket.room).emit('newMessage', message);
    });

    socket.on('room', function  (data) {
        console.log("Join room", data.room);
        socket.join(data.room);
        socket.room = data.room;
        socket.channel = data.channel;
        redisClient.subscribe('notification' + data.channel)
    })

    // When socket disconnects, remove it from the list:
    socket.on('disconnect', function() {
        var index = usernames.indexOf(socket);
        if (index != -1) {
            usernames.splice(index, 1);
            console.info('Client gone (id=' + socket.id + ').');
        }
        redisClient.unsubscribe('notification' + socket.channel);
        socket.leave(socket.room);
    });

    socket.on('disconnect', function() {
        redisClient.quit();
    });

});
```

## 8. Update Yii with chat form and to run Socket.io client script
> Add socket.io client script to the head section of main layout file (view/layouts/main.php):
```
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script src="https://cdn.socket.io/socket.io-1.3.5.js"></script>
    <?php $this->head() ?>
</head>
```
> After this, create new web/js/notification.js file with this content:
```
$( document ).ready(function() {
    $('#message-field').keyup(function(e) {
        if((e.keyCode == 13) && ($(this).val() != '')) {
            $('#chat-form').submit();
        }
    });

    var siteUrl = 'http://private-chat.dev:8890';
    var socket = io.connect(siteUrl);

    //update view html - no important
    var room = $('input[name="conversationId"]').val();
    var channel = $('input[name="channel"]').val();
    var from = $('input[name="from"]').val();

    if (typeof(room) != "undefined" && typeof(channel) != "undefined") {
        socket.on('newMessage', function (data) {
            var message = JSON.parse(data);
            if ($('#conversation-' + message.conversationId).length <= 0) {
                $(".chatLeftList").append("<li class='conversation-left' id='conversation-" + message.conversationId + "'>"
                    + '<div class="chatLeftBox">'
                    + "<div class='chatLeftBoxTtl clearfix'><a class='text-one-line user-fullname' href='" + message.userUrl + "' >" + message.name + "</a><span class='message-time'>" + message.time + "</span></div>"
                    + "<div class='chatLeftBoxCnt'><a class='chat-history text-one-line' href='" + message.userUrl + "'>" + message.message + "</a></div>"
                    + "</div></li>"
                );
            }
            else{
                $('#conversation-' + message.conversationId).find('.message-time').html(message.time);
                $('#conversation-' + message.conversationId).find('.chat-history').html(message.shortMessage);
            }
            if(from == message.to){
                $(".chatRList").append("<li class='clearfix we-chat we-chat-you'>"
                    + "<p class='avatar'><a href='" + message.userUrl + "' target='_blank'><img src='" + message.imageUrl + "' alt='' /></a></p>"
                    + "<div class='block-chat'><div class='chatRBox'>" + message.message + "</div></div>"
                    + "</li>"
                );
            }
            else{
                $(".chatRList").append("<li class='clearfix we-chat we-chat-me'>"
                    + "<div class='block-chat'><div class='chatRBox'>" + message.message + "</div></div>"
                    + "</li>"
                );
            }
            $(".chatRList").animate({scrollTop: $('.chatRList').prop("scrollHeight")}, 100);
        });
        socket.emit('room', {room: room, channel: channel});
    }
});
```
> We also must add notification.js reference to assets/AppAsset:
```
public $js = [
    'js/notification.js'
];
```
> Update your views/site/index.php view file with new chat form and notifications div:
<?php
```
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Message;
use app\components\CmsFormatter;

/* @var $this yii\web\View */

$this->title = 'Chat';

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
                                    $urlImage = 'https://private-chat.dev/image.jpg';
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
```

> At the end we also must update our controllers/SiteController::index() method:
```
public function actionChat()
{
    try {
        if (!Yii::$app->user->isGuest) {
            $user_id_to = Yii::$app->request->get('id'); //39 //38
            $userToModel = User::findIdentity($user_id_to);
            $user_id_from = Yii::$app->user->id;    //38 //39
            if (Yii::$app->request->post()) {
                $conversationId = Yii::$app->request->post('conversationId');
                $channel = trim(HtmlPurifier::process(Yii::$app->request->post('channel')));
                $message = trim(HtmlPurifier::process(Yii::$app->request->post('message')));
                $u = User::findIdentity(Yii::$app->user->id);

                $m = new Message();
                $m->conversation_id = $conversationId;
                $m->user_id_from = Yii::$app->user->id;
                $m->user_id_to = $channel;
                $m->created_date = date('Y-m-d H:i:s');
                $m->message = $message;
                $m->ip_address = $this->getRealIpAddr();
                $m->save();
                $name = $u->full_name;

                $time = date('D g:ia', strtotime($m->created_date));
                $userUrl = Yii::$app->urlManager->createUrl(['site/viewprofile', 'id' => $user_id_from]);
                $conversationUrl = Yii::$app->urlManager->createUrl(['site/chat', 'id' => $channel]);
                $imageUrl = 'https://private-chat.dev/image.jpg';
                $shortMessage = Message::createShort($message, 35);
                return Yii::$app->redis->executeCommand('PUBLISH', [
                    'channel' => 'notification'.$channel,
                    'message' => Json::encode([
                        'name' => $name, 
                        'message' => $message,
                        'shortMessage' => $shortMessage,
                        'conversationId' => $conversationId,
                        'time' => $time,
                        'userUrl' => $userUrl,
                        'imageUrl' => $imageUrl,
                        'conversationUrl' => $conversationUrl,
                        'to' => $channel
                    ])
                ]);
            }

            if ($user_id_from != $user_id_to) {
                $conversation = Conversation::checkExist($user_id_from, $user_id_to);
                if (!isset($conversation) || empty($conversation)) {
                    $conversation = new Conversation();
                    $conversation->user_id_from = $user_id_from;
                    $conversation->user_id_to = $user_id_to;
                    $conversation->created_date = date('Y-m-d H:i:s');
                    $conversation->save();
                }
            }
            $chatlog =  Conversation::getAllHistory($user_id_from, $user_id_to);
            $chatlogMenu =  Conversation::getAllHistoryMenu($user_id_from);

            return $this->render('chat', [
                'chatlog' => $chatlog, 
                'chatlogMenu' => $chatlogMenu,
                'conversationId' => $conversation->id,
                'channel' => $user_id_to,
                'userToModel' => $userToModel,
                'user_id_from' => $user_id_from
            ]);
        }
        else{
            $this->redirect('/');    
        }
    } catch (Exception $e) {
        $this->redirect('/');
    }
}
```

## 9. Running application    
> First, we must start Redis server (If not already) with (Navigate to Redis directory):

$ src/redis-server
> Next start Redis CLI monitor with:

$ src/redis-cli monitor
> Then run server.js with (Navigate to nodejs folder):

$ node server.js
