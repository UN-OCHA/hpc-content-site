<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\BaseEntityInterface;

/**
 * Custom action to move an entity to the trash.
 */
#[Action(
  id: 'entity_move_to_trash',
  label: new TranslatableMarkup('Move to trash')
)]
class MoveToTrash extends ContentActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity instanceof BaseEntityInterface) {
      return;
    }
    $entity->setDeleted();
    $entity->save();
    Cache::invalidateTags($entity->getCacheTags());
  }

}
