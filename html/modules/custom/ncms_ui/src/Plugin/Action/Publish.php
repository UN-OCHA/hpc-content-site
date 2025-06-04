<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class Publish extends ContentActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if (!$node instanceof NodeInterface || !$node instanceof ContentInterface) {
      return;
    }

    // Entity has not been changed, so we simply update the current
    // revision to published.
    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_storage->updateRevisionStatus($node, NodeInterface::PUBLISHED);
    Cache::invalidateTags($node->getCacheTags());
  }

}
