<?php

namespace Drupal\ncms_publisher\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Publisher entities.
 */
interface PublisherInterface extends ConfigEntityInterface {

  /**
   * Retrieve a list of known hosts.
   *
   * @return string[]
   *   An array with the defined known hosts for a publisher.
   */
  public function getKnownHosts();

  /**
   * Checks if the given host is known for this publisher.
   *
   * @param string $host
   *   The host to check.
   *
   * @return bool
   *   TRUE if the host is known, FALSE otherwise.
   */
  public function isKnownHost($host);

}
