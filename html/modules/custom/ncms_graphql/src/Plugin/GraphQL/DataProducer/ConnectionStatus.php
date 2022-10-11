<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Transforms a string to uppercase.
 *
 * @DataProducer(
 *   id = "connection_status",
 *   name = @Translation("Connection status"),
 *   description = @Translation("Returns a string if the connection has been successful."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Connection status")
 *   )
 * )
 */
class ConnectionStatus extends DataProducerPluginBase {

  /**
   * Value resolver.
   *
   * @return string
   *   A status string.
   */
  public function resolve() {
    return 'connected';
  }

}
