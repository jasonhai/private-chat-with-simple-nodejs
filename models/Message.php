<?php

namespace app\models;

use Yii;
use app\models\Conversation;
use app\models\User;
use app\components\helper\StringHelper;

/**
 * This is the model class for table "core_message".
 *
 * @property integer $id
 * @property integer $conversation_id
 * @property string $message
 * @property string $created_date
 * @property string $ip_address
 */
class Message extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'core_message';
    }

    public function getConversation()
    {
        return $this->hasOne(Conversation::className(), ['id' => 'conversation_id']);
    } 

    public function getUserFrom()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id_from']);
    } 

    public function getUserTo()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id_to']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['conversation_id', 'message', 'created_date'], 'required'],
            [['conversation_id'], 'integer'],
            [['message'], 'string'],
            [['created_date'], 'safe'],
            [['ip_address'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conversation_id' => 'Conversation ID',
            'message' => 'Message',
            'created_date' => 'Created Date',
            'ip_address' => 'Ip Address',
            'user_id_from' => 'From',
            'user_id_to' => 'To',
        ];
    }

    /**
     * trims text to a space then adds ellipses if desired
     * @param string $str text to trim
     * @param int $length in characters to trim to
     * @param bool $ellipses if ellipses (...) are to be added
     * @param bool $strip_html if html tags are to be stripped
     * @return string
     */
    public static function createShort($str, $length, $ellipses = true, $strip_html = true)
    {
        //strip tags, if desired
        if ($strip_html) {
            $str = strip_tags($str);
        }

        if(strlen($str) <= $length) return $str;

        $shortStr = trim(substr($str, 0 , $length - 3));

        //add ellipses (...)
        if ($ellipses) {
            $shortStr = trim($shortStr).'...';
        }

        return $shortStr;
    }

    public static function getLastMessage($conversation)
    {
        $result = Message::find()
                    ->where('conversation_id = :conversation_id', [':conversation_id' => $conversation->id])
                    ->orderBy(['created_date' => SORT_DESC])
                    ->one();
        return isset($result)? self::createShort($result->message, 35):'';
    } 

}
