<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Traits\ContentSpaceManagerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the MediaController class.
 */
final class MediaController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use ContentSpaceManagerTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->contentSpaceManager = $container->get('ncms_ui.content_space.manager');
    return $instance;
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
  public function mediaCreateAccess(AccountInterface $account) {
    return $this->getContentSpaceManager()->userIsInValidContentSpace($account) ? AccessResult::allowed() : AccessResult::neutral();
  }

}
