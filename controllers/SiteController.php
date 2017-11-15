<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;

//chat
use yii\helpers\Json;
use app\models\Message;
use app\models\Conversation;
use yii\helpers\HtmlPurifier;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
          $ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
          $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
          $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

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

                    echo "sa";die;
                 
                    $time = date('D g:ia', strtotime($m->created_date));
                    $userUrl = Yii::$app->urlManager->createUrl(['site/viewprofile', 'id' => $user_id_from]);
                    $conversationUrl = Yii::$app->urlManager->createUrl(['site/chat', 'id' => $channel]);
                    $imageUrl ='https://gogtour.com/upload/users/38/100x100/1496997657_38_1 SOPHIA ANNA.jpg';
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
}
