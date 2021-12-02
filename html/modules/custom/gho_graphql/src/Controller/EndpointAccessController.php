<?php

namespace Drupal\gho_graphql\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class that does access checking on graphql endpoints.
 */
class EndpointAccessController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Custom access check for graphql endpoints.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether access is granted for not.
   */
  public function checkAccess() {
    // See if we have a cookie.
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    $config = $this->config('gho_graphql.settings');
    if (!$config->get('access_key') || !$cookies->has('ghi_access') || $cookies->get('ghi_access') != $config->get('access_key')) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
