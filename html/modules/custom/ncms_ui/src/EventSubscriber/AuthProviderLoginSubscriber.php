<?php

namespace Drupal\ncms_ui\EventSubscriber;

use Drupal\ncms_ui\AuthProviderRoleManager;
use Drupal\social_auth\Event\LoginEvent;
use Drupal\social_auth\Event\SocialAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Assigns auth provider marker roles after social auth logins.
 */
class AuthProviderLoginSubscriber implements EventSubscriberInterface {

  /**
   * The auth provider role manager.
   *
   * @var \Drupal\ncms_ui\AuthProviderRoleManager
   */
  protected AuthProviderRoleManager $roleManager;

  /**
   * Constructor.
   *
   * @param \Drupal\ncms_ui\AuthProviderRoleManager $role_manager
   *   The auth provider role manager.
   */
  public function __construct(AuthProviderRoleManager $role_manager) {
    $this->roleManager = $role_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SocialAuthEvents::USER_LOGIN => 'onSocialAuthLogin',
    ];
  }

  /**
   * Assign the Google marker role when a user logs in with Google.
   *
   * @param \Drupal\social_auth\Event\LoginEvent $event
   *   The social auth login event.
   */
  public function onSocialAuthLogin(LoginEvent $event): void {
    $this->roleManager->assignRoleForProvider(
      $event->getDrupalAccount(),
      $event->getPluginId()
    );
  }

}
