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

  /**
   * Check if remote refresh notifications are enabled.
   *
   * @return bool
   *   TRUE if notifications are enabled, FALSE otherwise.
   */
  public function refreshNotificationsEnabled();

  /**
   * Get the remote refresh endpoint URL.
   *
   * @return string|null
   *   The endpoint URL or NULL if none is configured.
   */
  public function getRefreshEndpoint();

  /**
   * Get the remote refresh shared secret.
   *
   * @return string|null
   *   The shared secret or NULL if none is configured.
   */
  public function getRefreshSecret();

}
