<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\BaseEntityInterface;

/**
 * Custom action to unpublish an entity.
 */
#[Action(
  id: 'entity_unpublish',
  label: new TranslatableMarkup('Unpublish'),
)]
class Unpublish extends ContentActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity instanceof BaseEntityInterface) {
      return;
    }

    // Entity has not been changed, so we simply update the current
    // revision to unpublished.
    $storage = $entity->getEntityStorage();
    $storage->updateRevisionStatus($entity, 0);
    Cache::invalidateTags($entity->getCacheTags());
  }

}
