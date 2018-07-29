<?php

namespace app\models;

use yii\base\Model;

class IpFilterForm extends Model
{
    public $ip;

    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['ip'], 'ip'],
        ];
    }

}