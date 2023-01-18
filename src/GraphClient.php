<?php

namespace smavantel\graph;

use GuzzleHttp\Client as GuzzleClient;

class GraphClient extends GuzzleClient {

  var $tenantID;
  var $clientID;
  var $clientSecret;
  var $Token;
  var $baseURL;
  public $authCertFile = '';
  public $authKeyFile = '';

  public function init() {
    parent::init();
  }

  function __construct($config = []) {

    parent::__construct($config);
    $this->baseURL = 'https://graph.microsoft.com/v1.0/';
  }

  /**
   * 
   * @return \smavantel\graph\GraphToken
   */
  function getToken() {


    $url = 'https://login.microsoftonline.com/' . $this->tenantID . '/oauth2/v2.0/token';
    $response = $this->post($url, [
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