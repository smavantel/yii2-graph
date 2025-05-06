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
    return [
      "emailAddress" => [
        'name' => $emailAddress->getName(),
        'address' => $emailAddress->getAddress(),
      ]
    ];
  }

}
