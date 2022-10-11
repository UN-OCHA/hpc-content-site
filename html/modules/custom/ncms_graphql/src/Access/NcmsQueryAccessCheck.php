<?php

namespace Drupal\ncms_graphql\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Access\QueryAccessCheck;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\social_api\User\UserManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * An NCMS specific access check for graphql queries.
 */
class NcmsQueryAccessCheck implements AccessInterface {

  /**
   * Original service object.
   *
   * @var \Drupal\graphql\Access\QueryAccessCheck
   */
  protected $queryAccessCheck;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The social auth user manager.
   *
   * @var \Drupal\social_auth\User\UserManager
   */
  protected $userManager;

  /**
   * Constructs a new QueryAccessCheck instance.
   *
   * @param \Drupal\graphql\Access\QueryAccessCheck $query_access_check
   *   The original query access check service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\social_auth\User\UserManager $user_manager
   *   The social auth user manager service.
   */
  public function __construct(QueryAccessCheck $query_access_check, RequestStack $request_stack, UserManager $user_manager) {
    $this->queryAccessCheck = $query_access_check;
    $this->requestStack = $request_stack;
    $this->userManager = $user_manager;
    $this->userManager->setPluginId('social_auth_hid');
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\graphql\Entity\ServerInterface $graphql_server
   *   The server instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, ServerInterface $graphql_server) {
    if ($account->hasPermission('bypass graphql access')) {
      return AccessResult::allowed();
    }

    // First, if the original access check allows access, or if the request is
    // against a server that does not use the NCMS Schema, we accept whatever
    // the original service concludes.
    $access_result = $this->queryAccessCheck->access($account, $graphql_server);
    if ($graphql_server->schema != 'ncms_schema' || $access_result->isAllowed()) {
      return $access_result;
    }

    // First check if an access key has been passed in. This is to generally
    // limit requests to an implementing partner via a shared access key.
    $schema_configuration = $graphql_server->get('schema_configuration')[$graphql_server->schema];
    if (array_key_exists('require_access_key', $schema_configuration) && !empty($schema_configuration['require_access_key'])) {
      // Access key is required.
      if (array_key_exists('access_key', $schema_configuration) && !empty($schema_configuration['access_key'])) {
        $cookies = $this->requestStack->getCurrentRequest()->cookies;
        if (!$cookies->has('access_key') || $cookies->get('access_key') != $schema_configuration['access_key']) {
          // No access key has been given, or the given one doesn't match.
          return AccessResult::forbidden('Invalid access key given');
        }
      }
      else {
        // No access key has been given, or the given one doesn't match.
        return AccessResult::forbidden('No access key set');
      }
    }

    return AccessResult::allowed();
  }

}
