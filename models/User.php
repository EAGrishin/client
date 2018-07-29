<?php

namespace app\models;
use Yii;
use DateTime;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\base\NotSupportedException;

/**
 * User model
 *
 * @property integer $id
 * @property string $email
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $login_token
 * @property string $auth_key
 * @property string $role
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 * @property int $ip_create
 * @property string $password write-only password
 * @property string $subscription_expired_at
 */

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    const ROLE_REGISTERED = 'registered';

    public $password;


    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->ip_create = new Expression('INET_ATON(:ip)', [':ip' => Yii::$app->request->userIP]);
        }
        if (!empty($this->password)) {
            $this->setPassword($this->password);
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'password'],'required',],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            ['role', 'default', 'value' => self::ROLE_REGISTERED],
            [['subscription_expired_at'], 'date', 'format' => 'php:Y-m-d H:i:s'],
            [['email'], 'unique'],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }


    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return !empty($this->password_hash) && Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        if (!empty($password)) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        }
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new login token
     */
    public function generateLoginToken()
    {
        $this->login_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function increaseSubscribe($days)
    {
        if ($this->subscription_expired_at) {
            if ($this->subscription_expired_at < date('Y-m-d H:i:s')) {
                $begin = (new DateTime())->modify('today'); // начало текущего дня
            } else {
                $begin = (new DateTime($this->subscription_expired_at))->modify('today'); // начало дня, в который заканчивается подписка
            }
        } else {
            $begin = (new DateTime())->modify('today'); // начало текущего дня
        }
        $days++; // подписка должна закончится в конце последнего дня, поэтому прибавляем сутки
        $this->subscription_expired_at = date('Y-m-d H:i:s',
            strtotime($begin->format('Y-m-d H:i:s') . " +{$days} days"));
    }

    public function getSubscribeDays()
    {
        return floor((strtotime($this->subscription_expired_at) - time()) / (60 * 60 * 24));
    }


    public function isPremium()
    {
        return strtotime($this->subscription_expired_at) > time();
    }
}
