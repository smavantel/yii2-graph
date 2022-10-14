<?php

namespace smavantel\graph;

class GraphClient  extends \yii\base\Component {

  var $tenantID;
  var $clientID;
  var $clientSecret;
  var $Token;
  var $baseURL;

  public function init() {
    parent::init();
  }
  function __construct() {

    $this->baseURL = 'https://graph.microsoft.com/v1.0/';

  }

  function getTokeni() {
    $oauthRequest = 'client_id=' . $this->clientID . '&scope=https%3A%2F%2Fgraph.microsoft.com%2F.default&client_secret=' . $this->clientSecret . '&grant_type=client_credentials';
    $reply = $this->sendPostRequest('https://login.microsoftonline.com/' . $this->tenantID . '/oauth2/v2.0/token', $oauthRequest);
    $reply = json_decode($reply['data']);
    return $reply->access_token;
  }
  
  /**
   * 
   * @return \smavantel\graph\Token
   */
  
  function getToken() {

    $guzzle = new \GuzzleHttp\Client();
    $url = 'https://login.microsoftonline.com/' . $this->tenantID . '/oauth2/v2.0/token';
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
    return new \smavantel\graph\Token($data);
  }

  function basicAddress($addresses) {
    $ret = null;
    foreach ($addresses as $address) {
      $ret[] = $address->emailAddress->address;
    }
    return $ret;
  }

  function sendDeleteRequest($URL) {
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->Token, 'Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
  }

  function sendPostRequest($URL, $Fields, $Headers = false) {
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($Fields)
      curl_setopt($ch, CURLOPT_POSTFIELDS, $Fields);
    if ($Headers) {
      $Headers[] = 'Authorization: Bearer ' . $this->Token;
      curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return array('code' => $responseCode, 'data' => $response);
  }

  function sendGetRequest($URL) {
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->Token, 'Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
  }

}

?>