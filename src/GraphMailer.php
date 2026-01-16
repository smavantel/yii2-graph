<?php
namespace smavantel\graph;

use smavantel\graph\GraphMessage;
use smavantel\graph\GraphClient;
use Microsoft\Graph\Graph;
use Yii;


/**
 * Description of GraphMailer
 *
 * @author s.mager
 * 
 */
class GraphMailer extends \yii\mail\BaseMailer {

  /**
   * 
   * @var string
   */
  public $mailbox = 'mailer@example.com';

  /**
   * 
   * @var string
   */
  public $messageClass = 'smavantel\graph\GraphMessage';

  /**
   * 
   * @return Microsoft\Graph\Graph
   */
  public $client;

  /**
   *  'clientID' => '',
   *  'tenantID' => '',
   *  'clientSecret' => '',
   * @var string[]
   */
  public $clientConfig;

  /**
   * 
   * @var \Microsoft\Graph\Http\GraphResponse
   */
  public $response;

  /**
   * 
   * @var string[]
   */
  public $errors = [];

  /**
   * 
   * @var string[]
   */
  public $messages = [];

  /**
   * 
   * @var Message
   */
  public $graphMessage;

  /**
   * 
   * @var \Microsoft\Graph\Model\Attachment[]
   */
  private $_attachments;
  
  /**
   * 
   * @var GraphMessage
   */
  private $_message;

  /**
   * 
   * @return Graph
   */
  protected function getClient() {
    $graphClient = new GraphClient([
      'clientID' => $this->clientConfig['clientID'],
      'tenantID' => $this->clientConfig['tenantID'],
      'clientSecret' => $this->clientConfig['clientSecret'],
      ]
    );
    $graph = new Graph();
    $graph->setAccessToken($graphClient->getToken()->access_token);
    return $graph;
  }
  
  public function compose($view = null, array $params = []) {
    $message = $this->createMessage();
    if ($view === null) {
      // initilisiert das data['body']['content']
      $message->setHtmlBody('');
      return $message;
    }
    if (!array_key_exists('message', $params)) {
      $params['message'] = $message;
    }
    $this->_message = $message;
    if (is_array($view)) {
      if (isset($view['html'])) {
        $html = $this->render($view['html'], $params, $this->htmlLayout);
      }
      if (isset($view['text'])) {
        $text = $this->render($view['text'], $params, $this->textLayout);
      }
    } else {
      $html = $this->render($view, $params, $this->htmlLayout);
    }

    $this->_message = null;

    if (isset($html)) {
      $message->setHtmlBody($html);
    }
    if (isset($text)) {
      $message->setTextBody($text);
    } elseif (isset($html)) {
      if (preg_match('~<body[^>]*>(.*?)</body>~is', $html, $match)) {
        $html = $match[1];
      }
      // remove style and script
      $html = preg_replace('~<((style|script))[^>]*>(.*?)</\1>~is', '', $html);
      // strip all HTML tags and decoded HTML entities
      $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, Yii::$app ? Yii::$app->charset : 'UTF-8');
      // improve whitespace
      $text = preg_replace("~^[ \t]+~m", '', trim($text));
      $text = preg_replace('~\R\R+~mu', "\n\n", $text);
      /*
      if ($message->bodyType == 'text') {
        $message->setTextBody($text);
      }
       * 
       */
    }
    return $message;
  }

  /**
   * 
   * @param type $message
   * @return bool
   */
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

      \Yii::debug($data['from'],'graphMailer');

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
  
      /**
   * Renders the specified view with optional parameters and layout.
   * The view will be rendered using the [[view]] component.
   * @param string $view the view name or the [path alias](guide:concept-aliases) of the view file.
   * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
   * @param string|bool $layout layout view name or [path alias](guide:concept-aliases). If false, no layout will be applied.
   * @return string the rendering result.
   */
  public function render($view, $params = [], $layout = false) {
    $output = $this->getView()->render($view, $params, $this);
    if ($layout !== false) {
      $layoutParams = isset($params['layoutParams']) ? $params['layoutParams'] : null;
      $body = $this->getView()->render($layout, ['content' => $output, 'message' => $this->_message], $this);

      if ($layoutParams) {
        $body = strtr($body, $layoutParams);
      }
      return $body;
    }

    return $output;
  }

  public static function QuickSend($to, $subject, $body, $params = []) {

    if (!isset($params['mailer'])) {
      $mailer = Yii::$app->mailer;
    } else {
      $mailer = $params['mailer'];
    }

    $cc = isset($params['cc']) ? $params['cc'] : [];

    $from = ['mailer@avantel.de' => 'AVANTEL Mailer'];
    $message = $mailer->compose()
      ->setFrom($from)
      ->setTo($to)
      ->setcC($cc)
      ->setSubject($subject)
      ->setHtmlBody($body);
    if (isset($params['attachment'])) {
      $files = $params['attachment'];
      foreach ($files as $file => $name) {
        $message->attach($file, ['name' => $name]);
      }
    }

    $send = $message->send();
    return $send;
  }

}
