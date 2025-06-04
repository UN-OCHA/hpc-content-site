<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class Unpublish extends ContentActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if (!$node instanceof NodeInterface || !$node instanceof ContentInterface) {
      return;
    }

    // Entity has not been changed, so we simply update the current
    // revision to unpublished.
    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_storage->updateRevisionStatus($node, NodeInterface::NOT_PUBLISHED);
    Cache::invalidateTags($node->getCacheTags());
  }

}
