<?php

namespace smavantel\graph;

class GraphClient extends \yii\base\Component {

  var $tenantID;
  var $clientID;
  var $clientSecret;
  var $Token;
  var $baseURL = 'https://graph.microsoft.com/v1.0';
  public $authCertFile = '';
  public $authKeyFile = '';

  function __construct($config = []) {
    parent::__construct($config);
  }

  /**
   * 
   * @return string
   */
  public function getEndpoint($function = 'token') {
    return 'oauth2/v2.0/' . $function;
  }

  /**
   * 
   * @return \smavantel\graph\GraphToken
   */
  function getToken() {

    $guzzle = new \GuzzleHttp\Client();

    $this->baseURL = 'https://login.microsoftonline.com';
    $url = $this->baseURL . '/' . $this->tenantID . '/' . $this->getEndpoint();

    $response = $guzzle->post($url, [
      'form_params' => [
        'client_id' => $this->clientID,
        'client_secret' => $this->clientSecret,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials',
      ],
    ]);
    $content = $response->getBody()->getContents();
    $data = \yii\helpers\Json::decode($content);
    return new \smavantel\graph\GraphToken($data);
  }

}

?>