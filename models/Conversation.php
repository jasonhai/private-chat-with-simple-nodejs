<?php

namespace app\models;

use Yii;
use app\models\Message;
use app\models\User;

/**
 * This is the model class for table "core_conversation".
 *
 * @property integer $id
 * @property integer $user_id_from
 * @property integer $user_id_to
 * @property string $created_date
 */
class Conversation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'core_conversation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id_from', 'user_id_to', 'created_date'], 'required'],
            [['user_id_from', 'user_id_to'], 'integer'],
            [['created_date'], 'safe'],
        ];
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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id_from' => 'User Id From',
            'user_id_to' => 'User Id To',
            'created_date' => 'Created Date',
        ];
    }

    public static function checkExist($user_id_from, $user_id_to)
    {
        return self::find()
            ->where('user_id_from =:user_id_from and user_id_to =:user_id_to', [':user_id_from' => $user_id_from, ':user_id_to' => $user_id_to])
            ->orWhere('user_id_from =:user_id_to and user_id_to =:user_id_from', [':user_id_from' => $user_id_from, ':user_id_to' => $user_id_to])
            ->one();
    }

    public static function getAllHistory($user_id_from, $user_id_to)
    {
        $result = [];
        $c = self::find()
            ->where('user_id_from =:user_id_from and user_id_to =:user_id_to', [':user_id_from' => $user_id_from, ':user_id_to' => $user_id_to])
            ->orWhere('user_id_from =:user_id_to and user_id_to =:user_id_from', [':user_id_from' => $user_id_from, ':user_id_to' => $user_id_to])
            ->one();

        if (isset($c) && !empty($c)) {
            $result = Message::find()
                        ->where('conversation_id = :conversation_id', [':conversation_id' => $c->id])
                        ->all();
        }
        return $result;
    }    

    public static function getAllHistoryMenu($user_id_from)
    {
        $result = self::find()
            ->groupBy(['user_id_from', 'user_id_to'])
            ->having('user_id_from =:user_id_from or user_id_to =:user_id_from', [':user_id_from' => $user_id_from])
            ->orderBy(['created_date' => SORT_DESC])
            ->all();
        return $result;
    }

}
