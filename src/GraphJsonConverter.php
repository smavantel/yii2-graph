<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace common\modules\mail\components\graph;

/**
 * Description of GraphEmailAddress
 *
 * @author s.mager
 */
class GraphJsonConverter {

  public static function getRecipientAddress($recipient) {
    $emailAddress = $recipient->getEmailAddress();
    return [
      "emailAddress" => [
        'name' => $emailAddress->name,
        'address' => $emailAddress->address,
      ]
    ];
  }

}
