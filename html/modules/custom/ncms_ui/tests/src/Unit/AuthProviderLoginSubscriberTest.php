<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\ncms_ui\AuthProviderRoleManager;
use Drupal\ncms_ui\EventSubscriber\AuthProviderLoginSubscriber;
use Drupal\social_auth\Event\LoginEvent;
use Drupal\social_auth\User\SocialAuthUserInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Tests the auth provider login subscriber.
 *
 * @group ncms_ui
 */
class AuthProviderLoginSubscriberTest extends UnitTestCase {

  /**
   * Tests that the social auth login event delegates to the role manager.
   */
  public function testOnSocialAuthLogin(): void {
    $account = $this->prophesize(UserInterface::class)->reveal();
    $social_auth_user = $this->prophesize(SocialAuthUserInterface::class)->reveal();
    $event = new LoginEvent($account, $social_auth_user, 'social_auth_google');

    $role_manager = $this->prophesize(AuthProviderRoleManager::class);
    $role_manager->assignRoleForProvider($account, 'social_auth_google')
      ->shouldBeCalled();

    $subscriber = new AuthProviderLoginSubscriber($role_manager->reveal());
    $subscriber->onSocialAuthLogin($event);
  }

}
