<?php

namespace Drupal\gho_ncms;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manager clas for publisher config entities.
 */
class PublisherManager {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityBuffer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Retrieve a publisher entity by its id.
   *
   * @param string $id
   *   The id of the publisher.
   *
   * @return \Drupal\gho_ncms\Entity\PublisherInterface|null
   *   The publisher entity if found.
   */
  public function getPublisher($id) {
    $publisher = $this->entityTypeManager->getStorage('publisher')->loadByProperties(['id' => $id]);
    return $publisher ? reset($publisher) : NULL;
  }

}
