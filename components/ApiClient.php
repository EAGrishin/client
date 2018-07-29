<?php

namespace app\components;

use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use yii\base\Component;
use yii\base\Exception;

class ApiClient extends Component
{
    /** @var string api url */
    public $apiUrl = 'http://api.test/';

    /** @var Client */
    private $httpClient;

    /** @var string access_token */
    private $login_token;

    const METHOD_COUNTRY_GET = 'api/ip';


    public function init()
    {
        $this->httpClient = new Client(['base_uri' => $this->apiUrl]);
        $this->login_token = !Yii::$app->user->isGuest ? Yii::$app->user->identity->login_token : '';
    }

    /**
     * Возвращает название страны по ip
     * @param string $params
     * @return string
     */
    public function getСountry($params)
    {
        $result = $this->post(self::METHOD_COUNTRY_GET, $params);
        return $result;
    }

    /**
     * Выполнение запроса к api
     * @param string $method метод
     * @param array $data параметры запроса
     * @return mixed
     * @throws Exception
     */
    private function post($method, $data)
    {
        $body = json_encode($data);

        try {
            $response = $this->httpClient->post($method, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->login_token,
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'body' => $body,
                ],
            ]);

            return ['limit' => false, 'body' => json_decode((string)$response->getBody())];

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 429) {
                return ['limit' => true, 'body' => 'Please try again later or buy premium account'];
            } else {
                throw new Exception('Received unsuccessful response code');
            }
        }

    }


}