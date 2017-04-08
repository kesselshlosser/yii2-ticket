<?php

namespace ricco\ticket\models;

use \ricco\ticket\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use ricco\ticket\Module;

/**
 * This is the model class for table "ticket_head".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $department
 * @property string $topic
 * @property integer $status
 * @property string $date_update
 */
class TicketHead extends \yii\db\ActiveRecord
{

    public $user = false;

    /** @var  Module */
    private $module;

    /**
     * Статусы тикетов
     */
    const OPEN = 0;
    const WAIT = 1;
    const ANSWER = 2;
    const CLOSED = 3;
    const VIEWED = 4;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ticket_head}}';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->module = Module::getInstance();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'topic'], 'required'],
            [['user_id', 'status'], 'integer'],
            [['date_update'], 'safe'],
            [['department', 'topic'], 'string', 'max' => 255],
            [['department', 'topic'], 'filter', 'filter' => 'strip_tags'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'date_update',
                'updatedAtAttribute' => 'date_update',
                'value' => new Expression('NOW()'),
            ],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'user_id'     => 'User ID',
            'department'  => 'Отдел',
            'topic'       => 'Тема',
            'status'      => 'Status',
            'date_update' => 'Последнее обновление',
        ];
    }

    /**
     * dataProvider для пользователей
     *
     * @return ActiveDataProvider
     */
    public function dataProviderUser()
    {
        $query = TicketHead::find()->where("user_id = " . Yii::$app->user->id);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'date_update' => SORT_DESC
                ]
            ],
            'pagination' => [
                'pageSize' => $this->module->pageSize
            ]
        ]);

        return $dataProvider;
    }

    /**
     * dataProvider для админ панели
     *
     * @return ActiveDataProvider
     */
    public function dataProviderAdmin()
    {
        $query = TicketHead::find()->joinWith('userName');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'date_update' => SORT_DESC
                ]
            ],
            'pagination' => [
                'pageSize' => $this->module->pageSize
            ]
        ]);

        return $dataProvider;
    }

    public function getUserName()
    {
        $userModel = User::$user;
        return $this->hasOne($userModel, ['id' => 'user_id']);
    }

    public function getBody()
    {
        return $this->hasOne(TicketBody::className(), ['id_head' => 'id'])->orderBy('date DESC');
    }

    /**
     * @return int|string Возвращает количество новых тикетов статус которых OPEN или WAIT
     */
    public static function getNewTicketCount()
    {
        return TicketHead::find()->where('status = 0 OR status = 1')->count();
    }

    /**
     * Возвращает количество тикетов в по статусам
     *
     * @param int $status int Статус тикета
     * @return int|string
     */
    public static function getNewTicketCountUser($status = 0)
    {
        return TicketHead::find()->where("status = $status AND user_id = " . Yii::$app->user->id . " ")->count();
    }

    /**
     * Если это новый тикет записываем id пользователя который его создал
     *
     * @return bool
     */
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->user_id = ($this->user === false) ? Yii::$app->user->id : $this->user;
        }
        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }

}
