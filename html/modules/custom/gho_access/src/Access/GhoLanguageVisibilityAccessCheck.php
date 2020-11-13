<?php

namespace Drupal\gho_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Check global language visibility to determine access to a node.
 */
class GhoLanguageVisibilityAccessCheck implements AccessInterface {

  /**
   * The route matching service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route matching service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Check access to the node page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   The node that is being viewed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    // Only check the route for the node matching the current request.
    $nid = $this->routeMatch->getRawParameter('node');
    if ($nid === NULL || $nid != $node->id()) {
      // Let other modules decide the access to the node.
      return AccessResult::allowed();
    }

    // Check the node access for the current language. Skip the check on the
    // homepage if the current page is the homepage.
    $access = gho_access_check_language_access($account, $node->language()->getId(), $nid == 1);

    // Ensure the cache gets cleared when the permissions or the node change.
    $access->cachePerPermissions()->addCacheableDependency($node);

    // For better UX, we throw a page not found instead of a 403 but make sure
    // the response can be invalidated when the node, homepage or permissions
    // change.
    if ($access->isForbidden()) {
      throw new CacheableNotFoundHttpException($access);
    }

    // Route access is different than node access and "neutral" results in
    // forbidden acccess to we return a "allowed" inheriting the caching.
    return AccessResult::allowed()->inheritCacheability($access);
  }

}
