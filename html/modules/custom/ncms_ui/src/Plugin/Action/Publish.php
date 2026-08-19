<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Custom action to publish a content entity.
 */
#[Action(
  id: 'content_entity_publish',
  label: new TranslatableMarkup('Publish'),
  type: 'node'
)]
class Publish extends ContentActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if (!$node instanceof NodeInterface || !$node instanceof ContentInterface) {
      return;
    }

    self::getContentRevisionWorkflow()->publishExistingRevision($node);
    Cache::invalidateTags($node->getCacheTags());
  }

}
