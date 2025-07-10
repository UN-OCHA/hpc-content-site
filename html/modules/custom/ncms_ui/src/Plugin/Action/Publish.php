<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\BaseEntityInterface;

/**
 * Custom action to publish an entity.
 */
#[Action(
  id: 'entity_publish',
  label: new TranslatableMarkup('Publish'),
)]
class Publish extends ContentActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity instanceof BaseEntityInterface) {
      return;
    }

    // Entity has not been changed, so we simply update the current
    // revision to published.
    $storage = $entity->getEntityStorage();
    $storage->updateRevisionStatus($entity, 1);
    Cache::invalidateTags($entity->getCacheTags());
  }

}
