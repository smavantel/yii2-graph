<?php

namespace smavantel\graph;

/**
 * Description of GraphEmailAddress
 *
 * @author s.mager
 */
class GraphJsonConverter {

  public static function getRecipientAddress($recipient) {
    $emailAddress = $recipient->getEmailAddress();
    $r = [
      "emailAddress" => [
        'name' => $emailAddress->name,
        'address' => $emailAddress->address,
      ]
    ];
    return $r;
  }
}
