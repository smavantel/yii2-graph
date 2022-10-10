<?php
namespace smavantel\graph;

class Token extends \yii\base\Model {

  public $token_type;
  public $expires_in;
  public $ext_expires_in;
  public $access_token;

  public function rules(): array {
    return [
      [['token_type', 'expires_in', 'ext_expires_in', 'access_token'], 'safe']
    ];
  }

}
