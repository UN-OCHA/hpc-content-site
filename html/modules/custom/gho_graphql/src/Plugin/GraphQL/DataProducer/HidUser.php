<?php

namespace Drupal\gho_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\social_auth\User\UserManager;
use Drupal\social_auth\User\UserAuthenticator;

/**
 * Gets the HID user as identified by token in the request.
 *
 * @DataProducer(
 *   id = "hid_user",
 *   name = @Translation("HID user"),
 *   description = @Translation("HID user."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("HID user")
 *   )
 * )
 */
class HidUser extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The social auth user manager.
   *
   * @var \Drupal\social_auth\User\UserManager
   */
  protected $userManager;

  /**
   * The social auth user manager.
   *
   * @var \Drupal\social_auth\User\UserAuthenticator
   */
  protected $userAuthenticator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth.user_authenticator')
    );
  }

  /**
   * CurrentUser constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\social_auth\User\UserManager $user_manager
   *   The Social Auth user management service.
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   The Social Auth user authenticator service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, RequestStack $request_stack, AccountProxyInterface $current_user, UserManager $user_manager, UserAuthenticator $user_authenticator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->userManager = $user_manager;
    $this->userManager->setPluginId('social_auth_hid');
    $this->userAuthenticator = $user_authenticator;
  }

  /**
   * Returns current user.
   *
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field_context
   *   Field context.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  public function resolve(FieldContext $field_context): AccountInterface {
    // Get the HID user id from the request.
    /** @var \Symfony\Component\HttpFoundation\HeaderBag $headers */
    $headers = $this->requestStack->getMasterRequest()->headers;
    $hid_user_id = $headers->has('hid-user') ? $headers->get('hid-user') : NULL;
    if (!$hid_user_id) {
      // None given, so we fallback to the current user, which will be
      // anonymous.
      return $this->currentUser;
    }
    $user_id = $hid_user_id ? $this->userManager->getDrupalUserId($hid_user_id) : NULL;
    $drupal_user = $user_id ? $this->userManager->loadUserByProperty('uid', $user_id) : NULL;
    return $drupal_user && $drupal_user->isAuthenticated() && $drupal_user->isActive() ? $drupal_user : NULL;
  }

}
