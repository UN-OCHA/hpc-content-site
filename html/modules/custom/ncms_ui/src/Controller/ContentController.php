<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\ContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the ViewController class.
 */
class ContentController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\ncms_ui\ContentManager
   */
  protected $contentManager;

  /**
   * Creates a ContentController object.
   *
   * @param \Drupal\ncms_ui\ContentManager $content_manager
   *   The content manager.
   */
  public function __construct(ContentManager $content_manager) {
    $this->contentManager = $content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ncms_ui.content.manager')
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
    return $this->contentManager->userIsInValidContentSpace() ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
