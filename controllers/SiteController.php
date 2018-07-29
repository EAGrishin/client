<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\SignupForm;
use app\models\IpFilterForm;
use app\models\User;
use yii\web\BadRequestHttpException;

class SiteController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login','signup'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['error'],
                    ],
                    [
                        'actions' => ['index','logout', 'ajax-get-country', 'get-premium'],
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
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
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
        return $this->render('index', [
            'model' => new IpFilterForm(),
        ]);

    }

    public function actionAjaxGetCountry()
    {
        if (!Yii::$app->request->isAjax) {
            throw new BadRequestHttpException();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new IpFilterForm();
        if ($model->load(Yii::$app->request->post())) {
            $ip = $model->ip;
            $response = Yii::$app->api->getСountry($ip);
            return [
                'success' => true,
                'limit' => $response['limit'],
                'data' => $response['body']
            ];
        }
        return [
            'success' => false
        ];
    }

    public function actionGetPremium($days = 5)
    {
        $user = User::findOne(['id' => Yii::$app->user->id]);
        $user->increaseSubscribe($days);
        $user->save(false);
        Yii::$app->getSession()->setFlash('success',
            "Congratulations, you got {$days} Premium days!");
        $this->redirect(['site/index']);
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
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user, 3600 * 24 * 30)) {
                    return $this->goBack();
                }
            }
        }
        return $this->render('signup', [
            'model' => $model,
        ]);
    }
}
