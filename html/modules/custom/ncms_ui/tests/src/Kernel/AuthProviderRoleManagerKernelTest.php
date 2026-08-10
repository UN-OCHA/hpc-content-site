<?php

namespace Drupal\Tests\ncms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\AuthProviderRoleManager;
use Drupal\user\Entity\User;

/**
 * Tests auth provider role management.
 *
 * @group ncms_ui
 */
class AuthProviderRoleManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * The role manager under test.
   *
   * @var \Drupal\ncms_ui\AuthProviderRoleManager
   */
  protected AuthProviderRoleManager $roleManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    $this->roleManager = new AuthProviderRoleManager(
      $this->container->get('entity_type.manager')
    );
    $this->roleManager->ensureRolesExist();
  }

  /**
   * Tests that provider roles are created and assigned.
   */
  public function testAssignRoleForProvider(): void {
    $account = User::create([
      'name' => 'provider-user',
      'mail' => 'provider-user@example.com',
      'status' => 1,
    ]);
    $account->save();

    $this->assertTrue($this->roleManager->assignRoleForProvider($account, 'social_auth_google'));
    $this->assertFalse($this->roleManager->assignRoleForProvider($account, 'social_auth_google'));

    $reloaded = User::load($account->id());
    $this->assertTrue($reloaded->hasRole('google_login'));
    $this->assertFalse($reloaded->hasRole('un_agency_login'));
  }

  /**
   * Tests that both provider roles can coexist on one account.
   */
  public function testAssignMultipleProviderRoles(): void {
    $account = User::create([
      'name' => 'multi-provider-user',
      'mail' => 'multi-provider-user@example.com',
      'status' => 1,
    ]);
    $account->save();

    $this->assertTrue($this->roleManager->assignRoleForProvider($account, 'social_auth_google'));
    $this->assertTrue($this->roleManager->assignRoleForProvider($account, 'entraid'));

    $reloaded = User::load($account->id());
    $this->assertTrue($reloaded->hasRole('google_login'));
    $this->assertTrue($reloaded->hasRole('un_agency_login'));
  }

}
