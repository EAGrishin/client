<?php
namespace app\models;

use app\models\User;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\VarDumper;


/**
 * Signup form
 */
class SignupForm extends Model
{

    public $email;
    public $password;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            [
                'email',
                'unique',
                'targetClass' => '\app\models\User',
                'message' => 'This email is already registered.',
                'filter' => ['status' => User::STATUS_ACTIVE],
            ],
            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    /**
     * @param bool $sendEmail
     * @return User|null
     * @throws Exception
     */
    public function signup()
    {
        if ($this->validate()) {
            $user = new User();
            $user->email = $this->email;
            $user->password = $this->password;
            $user->generateAuthKey();
            $user->generateLoginToken();
            if (!$user->save()) {
                throw new Exception('Error save user: ' . VarDumper::dumpAsString($user->errors));
            }
            return $user;
        }
        return null;
    }
}
