<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the ContentController class.
 */
class ContentController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

  /**
   * Creates a ContentController object.
   *
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   */
  public function __construct(ContentSpaceManager $content_manager) {
    $this->contentSpaceManager = $content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ncms_ui.content_space.manager')
    );
  }

  /**
   * Custom access callback for node create.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that tries to access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public function nodeCreateAccess(AccountInterface $account) {
    return $this->contentSpaceManager->userIsInValidContentSpace() ? AccessResult::allowed() : AccessResult::neutral();
  }

  /**
   * Custom access callback for the versions page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that tries to access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public function versionAccess(NodeInterface $node, AccountInterface $account) {
    $access = $node->access('update', $account) && $node->access('view revision', $account);
    return $access ? AccessResult::allowed() : AccessResult::neutral();
  }

}
