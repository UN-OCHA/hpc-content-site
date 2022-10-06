<?php

namespace Drupal\ncms_publisher;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manager class for publishers.
 *
 * This allows to load publishers from the current request.
 */
class PublisherManager {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * PublisherManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $request_stack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Retrieve a publisher entity by its id.
   *
   * @param string $id
   *   The id of the publisher.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface|null
   *   The publisher entity if found.
   */
  public function getPublisher($id) {
    $publisher = $this->entityTypeManager->getStorage('publisher')->loadByProperties(['id' => $id]);
    return $publisher ? reset($publisher) : NULL;
  }

  /**
   * Get the current publisher from the request.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface|null
   *   The publisher entity if available.
   */
  public function getCurrentPublisher() {
    $query = $this->request->query;
    if (!$query->has('publisher')) {
      return NULL;
    }
    return $this->getPublisher($query->get('publisher'));
  }

  /**
   * Get the current publisher redirect response.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response as given by the publisher in the query arguments.
   *   Before returning, the destination is validated against a whitelist of
   *   known hosts.
   */
  public function getCurrentRedirectResponse() {
    $publisher = $this->getCurrentPublisher();
    if (!$publisher) {
      return NULL;
    }
    $query = $this->request->query;
    if (!$query->has('publisher_destination')) {
      return NULL;
    }
    $publisher_destination = $query->get('publisher_destination');
    $host = parse_url($publisher_destination, PHP_URL_HOST);
    if (!$host) {
      return NULL;
    }
    if (!$publisher->isKnownHost($host)) {
      return NULL;
    };
    return new TrustedRedirectResponse($publisher_destination);
  }

}
