<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Custom action to move a content entity to the trash.
 */
#[Action(
  id: 'content_entity_move_to_trash',
  label: new TranslatableMarkup('Move to trash'),
  type: 'node'
)]
class MoveToTrash extends ContentActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if (!$node instanceof NodeInterface || !$node instanceof ContentInterface) {
      return;
    }
    $node->setDeleted();
    $node->save();
    Cache::invalidateTags($node->getCacheTags());
  }

}
