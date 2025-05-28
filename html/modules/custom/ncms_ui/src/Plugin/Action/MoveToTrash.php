<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom action to move a node to the trash.
 *
 * @Action(
 *   id = "move_to_trash",
 *   label = @Translation("Move to trash"),
 *   type = "node"
 * )
 */
class MoveToTrash extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

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

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object instanceof NodeInterface || !$object instanceof ContentInterface) {
      return $return_as_object ? FALSE : AccessResult::forbidden();
    }
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
