<?php

namespace smavantel\graph;

use smavantel\graph\GraphMessage;

/**
 * Description of GraphMailer
 *
 * @author s.mager
 */
class GraphMailer extends \yii\mail\BaseMailer {

  public $mailbox = 'mailer@example.com';
  
  public $messageClass = 'smavantel\graph\GraphMessage';
  

  /**
   * 
   * @return Microsoft\Graph\Graph
   */

  public $client;
  
  public $clientConfig;
  
  /**
   * 
   * @var \Microsoft\Graph\Http\GraphResponse
   */
  
  public $response;
  
  public $errors = [];
  public $messages = [];

  /**
   * 
   * @var Message
   */
  public $graphMessage;
  private $_attachments;

  /**
   * 
   * @param GraphMessage $message
   * @return bool
   */
  protected function getClient() {
    $graphClient = new GraphClient([
      'clientID' => $this->clientConfig['clientID'],
      'tenantID' => $this->clientConfig['tenantID'],
      'clientSecret' => $this->clientConfig['clientSecret'],
      ]
    );
    $graph = new Microsoft\Graph\Graph();
    $graph->setAccessToken($graphClient->getToken()->access_token);
    return $graph;
  }

  protected function sendMessage($message): bool {

    $this->graphMessage = $message->getMessage();

    if ($this->graphMessage->getFrom()) {
      $data ['from'] = GraphJsonConverter::getRecipientAddress($this->graphMessage->getFrom());
    }

    $messageId = $this->graphMessage->getId();
    
    if (!$messageId) {
      $data ['subject'] = $message->data['subject'];
      $body = $message->data['body']['content'];
      if (array_key_exists('signature', $message->data)) {
        $body .= $message->data['signature']['content'];
      }

      

      $data ['body'] = [
        'contentType' => 'html',
        'content' => $body
      ];

      if (isset($message->data['toRecipients'])) {
        $data['toRecipients'] = $message->data['toRecipients'];
      }

      if (isset($message->data['ccRecipients'])) {
        $data['ccRecipients'] = $message->data['ccRecipients'];
      }
      if (isset($message->data['bccRecipients'])) {
        $data['bccRecipients'] = $message->data['bccRecipients'];
      }

      $validator = new \yii\validators\EmailValidator();
      $recpTypes = GraphMessage::getRecipientTypes();
      foreach ($recpTypes as $rt) {

        if (isset($data[$rt])) {
          $dataRt = $data[$rt];
          foreach ($dataRt as $n => $ea) {
            if (!$validator->validate($ea['emailAddress']['address'])) {
              $this->messages[] = $ea['emailAddress']['address'] . ' ist keine valide E-Mail und wurde entfernt.';
              unset($dataRt[$n]);
            }
          }
          unset($data[$rt]);
          if (count($dataRt) == 0) {
            $this->messages[] = 'Empfänger Typ: ' . $rt . ' ist nicht enthalten';
          } else {
            foreach ($dataRt as $v) {
              $data[$rt][] = $v;
            }
          }
        }
      }

      if ($this->client == null) {
        $this->client = $this->getClient();
      }


      $requestCreate = $this->client->createRequest("POST", '/users/' . $this->mailbox . '/messages')
        ->attachBody($data);
      $responseCreate = $requestCreate->execute();
      $this->graphMessage = new \Microsoft\Graph\Model\Message($responseCreate->getBody());
    }


    if ($this->graphMessage->getId()) {
      if (array_key_exists('attachments', $message->data)) {
        $attachments = $message->data['attachments'];
        foreach ($attachments as $attachment) {
          $requestAttach = $this->client->createRequest("POST", '/users/' . $this->mailbox . '/messages/' . $this->graphMessage->getId() . '/attachments')
            ->attachBody(\yii\helpers\Json::encode($attachment['requestParams']));
          $responseAttach = $requestAttach->execute();
          $attachment = new \Microsoft\Graph\Model\Attachment($responseAttach->getBody());
          $this->_attachments[] = $attachment;
        }
      }
      if (array_key_exists('signature', $message->data)) {
        $requestAttachLogo = $this->client->createRequest("POST", '/users/' . $this->mailbox . '/messages/' . $this->graphMessage->getId() . '/attachments')
          ->attachBody(\yii\helpers\Json::encode($message->data['signature']['requestParams']));
        $responseAttachLogo = $requestAttachLogo->execute();
        $attachment = new \Microsoft\Graph\Model\Attachment($responseAttachLogo->getBody());
        $this->_attachments [] = $attachment;
      }


      $id = $this->graphMessage->getId();
      $request = $this->client->createRequest("POST", '/users/' . $this->mailbox . '/messages/' . $id . '/send');
      try {
        $response = $request->execute();
      } catch (\GuzzleHttp\Exception\ClientException $e) {
        $this->errors[] = $e->getMessage();
      }
      if (!$this->errors) {
        $this->response = $response;
        $this->messages[] = get_class($response) . ' ' . $response->getStatus();
        $this->messages['mid'] = $id;
      }
    } else {
      $this->errors[] = 'no message id';
    }

    //$this->messages[] = $data;
    $message->setMessage($this->graphMessage);

    if (count($this->errors) > 0) {
      return false;
    }

    return true;
  }

}
