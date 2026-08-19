<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Custom action to unpublish a content entity.
 */
#[Action(
  id: 'content_entity_unpublish',
  label: new TranslatableMarkup('Unpublish'),
  type: 'node'
)]
class Unpublish extends ContentActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if (!$node instanceof NodeInterface || !$node instanceof ContentInterface) {
      return;
    }

    self::getContentRevisionWorkflow()->unpublishExistingRevision($node);
    Cache::invalidateTags($node->getCacheTags());
  }

}
