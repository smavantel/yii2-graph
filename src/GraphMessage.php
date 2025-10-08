<?php

namespace smavantel\graph;
use Microsoft\Graph\Model\Message;
use yii\mail\BaseMessage;
use yii\base\InvalidConfigException;

use smavantel\graph\ms\EmailAddress;




/**
 * Description of MailMessageGraph
 *
 * @author s.mager
 * 16.07.2024
 */
class GraphMessage extends BaseMessage {

  /**
   * 
   * @var Message
   */
  private $_message;
  
  
  public $data;
  /**
   * nur 'text' oder 'html' möglich
   * @var string
   */
  public $bodyType;
  
  CONST RECIPIENTS_TO = 'toRecipients';
  CONST RECIPIENTS_CC = 'ccRecipients';
  CONST RECIPIENTS_BCC = 'bccRecipients';
  CONST RECIPIENTS_REPLY_TO = 'replyTo';

  /**
   * 
   */
  private static $recipientTypes = [
    'toRecipients',
    'ccRecipients',
    'bccRecipients',
    'replyTo',
  ];
  
  
  public function init() {
    parent::init();
    $this->data = $this->initData();
  }
  
  
  /**
   * 
   * https://learn.microsoft.com/en-us/graph/api/resources/message?view=graph-rest-1.0
   * es gibt noch mehr, ggf. ergänzen
   * @return string[]
   */
  
  public function initData() {
    $data = [
      'subject' => '',
      'body' => [
        'contentType' => '',
        'content' => '',
      ],
      /*
      'attachments' => [],
      'signature' => [
        'content' => '',
        'requestParams' => ''
      ]
       * 
       */
    ];
    return $data;
  }
  
  /**
   * 
   * @return string[]
   */

  public static function getRecipientTypes() {
    return self::$recipientTypes;
  }

  /**
   * 
   * @return Message
   */
  public function getMessage() {
    if ($this->_message == null) {
      $this->_message = new Message();
    }
    return $this->_message;
  }

  public function setMessage($v) {
    
      $this->_message = $v;

  }

  public function getSubject(): ?string {
    return $this->getMessage()->getSubject();
  }

  public function getFrom() {
    return $this->getMessage()->getFrom();
  }

  public function getBcc() {
    ;
  }

  public function getCc() {
    ;
  }

  public function getTo() {
    ;
  }

  public function getCharset(): string {
    return 'UTF-8';
  }

  public function getReplyTo() {
    ;
  }

  public function setBcc($bcc): self {
    return $this->setRecipient(self::RECIPIENTS_BCC, $bcc);
  }

  public function setCc($cc): self {

     return $this->setRecipient(self::RECIPIENTS_CC, $cc);
  }

  public function setCharset($charset): self {
    ;
  }
  
  public function setFrom($from): self {
    if (is_array($from)) {
      $address = key($from);
      $name = $from[$address];
    } else {
      $address = $from;
      $name = null;
    }
    $emailAdress = new EmailAddress();
    $emailAdress->address = $address;
    if ($name) {
      $emailAdress->name = $name;
    }
    $recipient = new \Microsoft\Graph\Model\Recipient();
    $recipient->setEmailAddress($emailAdress);

    $message = $this->getMessage();
    $message->setFrom($recipient);
    $this->setMessage($message);

    return $this;
  }
  
    public function setSubject($subject): self {
    $this->data['subject'] = $subject;
    $this->getMessage()->setSubject($subject);
    return $this;
  }

  public function setBody($html, $type='html'): self {
    $this->bodyType = $type;
    $this->data ['body'] = [
      'contentType' => $type,
      'content' => $html,
    ];
    return $this;
  }

  public function setHtmlBody($html): self {
    return $this->setBody($html, 'html');
  }

  public function setTextBody($text): self {
    if ($this->bodyType == null) {
      return $this->setBody($text, 'text');
    }
    return $this;
  }

  public function getHtmlBody(): self {
    return $this->data['body']['content'];
  }





  public function setRecipient($typ, $address): self {
    if ($address) {
      if (!isset($this->data[$typ])) {
        $this->data[$typ] = [];
      }
      $addresses = $this->convertAddress($address);
      
      $this->data[$typ] = $addresses;
    }

    return $this;
  }

  public function setTo($to): self {
    return $this->setRecipient(self::RECIPIENTS_TO, $to);
  }
  
    public function setReplyTo($replyTo): self {
    return $this->setRecipient(self::RECIPIENTS_REPLY_TO, $replyTo);
  }


  /**
   * 
   * @return string
   */
  public function toString() {
    return self::class;
  }
  
  

  public function embed($fileName, array $options = []): string {
    $options['isInline'] = true;
    return $this->attach($fileName, $options);
  }

  public function embedContent($content, array $options = []): string {
     $options['isInline'] = true;
    return $this->attachContent($content, $options);
  }
  
  /**
   * 
   * @param string $content
   * @param array $options
   * @return $this
   */

  public function attachContent($content, array $options = []): self {
    $m['@odata.type'] = '#microsoft.graph.fileAttachment';
    $m['contentBytes'] = base64_encode($content);
    $m['isInline'] = isset($options['isInline']) ? $options['isInline'] : false;
    if (isset($options['contentId'])) {
      $m['contentId'] = $options['contentId'];
    }
    $attachment['requestParams'] = $m;
    if (!isset($this->data['attachments'])) {
      $this->data['attachments'] = [];
    }
    $this->data['attachments'][] = $attachment;
  }
  
  /**
   * 
   * @param string $fileName
   * @param array $options
   * @return $this
   */

  public function attach($fileName, array $options = []): self {
    if (file_exists($fileName)) {
      $content = file_get_contents($fileName);
      $attachment['fileName'] = $fileName;
      $split = explode('/', $fileName);
      $name = isset($options['name']) ? $options['name'] : $split[count($split) - 1];
      $m['@odata.type'] = '#microsoft.graph.fileAttachment';
      $m['name'] = $name;
      $m['contentType'] = mime_content_type($fileName);
      $m['contentBytes'] = base64_encode($content);
      $m['isInline'] = isset($options['isInline']) ? $options['isInline'] : false;
      if (isset($options['contentId'])) {
        $m['contentId'] = $options['contentId'];
      }
      $attachment['requestParams'] = $m;

      if (!isset($this->data['attachments'])) {
        $this->data['attachments'] = [];
      }
      $this->data['attachments'][] = $attachment;
    }
    return $this;
  }

  public function attachSignature($signatur, $options) {

    $m = null;
    if (file_exists($options['logoImage'])) {
      $split = explode('/', $options['logoImage']);
      $name = isset($options['name']) ? $options['name'] : $split[count($split) - 1];
      $m = [
        '@odata.type' => '#microsoft.graph.fileAttachment',
        "name" => $name,
        "contentType" => mime_content_type($options['logoImage']),
        "contentBytes" => base64_encode(file_get_contents($options['logoImage'])),
        'isInline' => true,
        'contentId' => $options['contentId']
      ];
    } else {
      if (isset($options['logoImage'])) {
        throw new InvalidConfigException('Die Datei ' . $options['logoImage'] . ' existiert nicht');
      }
      //new Exception('LogoImage fehlt');
    }

    $this->data['signature']['content'] = $signatur;
    if ($m != null) {
      $this->data['signature']['requestParams'] = $m;
    }
    return $this;
  }

  /**
   * 
   * @param type $emailName
   * @return array
   * Converts
   * ['email@domain.com'=>'Name']
   * into
   * [
   * 'address' => 'email@domain.com', 
   * 'name' => 'Name', 
   * ] 
   */
  
  public function convertAddress($emailName) {
    if (!is_array($emailName)) {
      $emailNamen[$emailName] = '';
      $emailName = $emailNamen;
    }
    $addresses = [];
    foreach ($emailName as $address => $name) {
      if (is_int($address)) {
        $emailAddress['address'] = $name;
      } else {
        $emailAddress['address'] = $address;
        if ($name) {
          $emailAddress['name'] = $name;
        }
      }
      $addresses[] = ['emailAddress' => $emailAddress];
    }
    return $addresses;
  }
}
