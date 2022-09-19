<?php

namespace smavantel\graph;

use yii\mail\MailerInterface;
use yii\base\ViewContextInterface;

class Mailer extends \yii\base\Component implements MailerInterface, ViewContextInterface {

  public function compose($view = null, array $params = []) {
    
  }

  public function send($message): bool {
    ;
  }

  public function sendMultiple(array $messages): int {
    ;
  }

  public function getViewPath(): string {
    ;
  }

}
