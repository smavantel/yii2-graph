<?php

namespace smavantel\graph;

use GuzzleHttp\Client as GuzzleClient;

class GraphClient extends GuzzleClient {

  var $tenantID;
  var $clientID;
  var $clientSecret;
  var $Token;
  var $baseURL = 'https://graph.microsoft.com/v1.0/';
  public $authCertFile = '';
  public $authKeyFile = '';

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