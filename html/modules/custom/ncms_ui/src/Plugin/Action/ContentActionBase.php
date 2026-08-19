<?php

namespace Drupal\ncms_ui\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\ncms_ui\ContentRevisionWorkflow;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Base class for content actions.
 */
abstract class ContentActionBase extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object instanceof NodeInterface || !$object instanceof ContentInterface) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Gets the content revision workflow service.
   *
   * @return \Drupal\ncms_ui\ContentRevisionWorkflow
   *   The content revision workflow service.
   */
  protected static function getContentRevisionWorkflow(): ContentRevisionWorkflow {
    return \Drupal::service('ncms_ui.content_revision_workflow');
  }

}
