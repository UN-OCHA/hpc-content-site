<?php

namespace Drupal\Tests\ncms_ui\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user_expire\Form\UserExpireSettingsForm;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the site's inactivity limits with User Expire's existing processing.
 */
#[Group('ncms_ui')]
class UserExpirationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'user_expire'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installSchema('user_expire', ['user_expire']);
    $this->installConfig(['system', 'user', 'user_expire']);

    // Exercise the small integration hooks without enabling unrelated content
    // services and their dependencies.
    require_once dirname(__DIR__, 3) . '/ncms_ui.module';

    Role::create(['id' => 'administrator', 'label' => 'Administrator'])->save();
    $settings = Yaml::decode(file_get_contents(dirname(__DIR__, 7) . '/config/user_expire.settings.yml'));
    unset($settings['_core']);
    $this->config('user_expire.settings')->setData($settings)->save();
  }

  /**
   * Tests editor/admin boundaries, never-used accounts and emergency access.
   */
  public function testInactivityLimits(): void {
    $now = $this->container->get('datetime.time')->getRequestTime();
    $service_account = User::create([
      'uid' => 1,
      'name' => 'service-account',
      'status' => 1,
      'access' => $now - 366 * 86400,
    ]);
    $service_account->save();

    $cases = [
      'editor-after-six-months' => [[], 200 * 86400, FALSE, FALSE],
      'editor-before-expiration' => [[], 31536000 - 1, FALSE, FALSE],
      'editor-at-expiration' => [[], 31536000, FALSE, TRUE],
      'admin-before-expiration' => [['administrator'], 15552000 - 1, FALSE, FALSE],
      'admin-at-expiration' => [['administrator'], 15552000, FALSE, TRUE],
      'unused-editor-before-expiration' => [[], 364 * 86400, TRUE, FALSE],
      'unused-editor-at-expiration' => [[], 31536000, TRUE, TRUE],
      'unused-admin-at-expiration' => [['administrator'], 15552000, TRUE, TRUE],
    ];
    $accounts = [];
    foreach ($cases as $name => [$roles, $inactivity, $never_accessed]) {
      $last_activity = $now - $inactivity;
      $account = User::create([
        'name' => $name,
        'status' => 1,
        'roles' => $roles,
        'created' => $last_activity,
        'access' => $never_accessed ? 0 : $last_activity,
      ]);
      $account->save();
      $accounts[$name] = $account->id();
    }

    user_expire_expire_by_role();
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $storage->resetCache();
    foreach ($accounts as $name => $uid) {
      $this->assertSame($cases[$name][3], $storage->load($uid)->isBlocked(), $name);
    }

    // Reactivating an expired account must allow time to return to the site.
    $admin = $storage->load($accounts['admin-at-expiration']);
    $admin->activate()->save();
    user_expire_expire_by_role();
    $storage->resetCache();
    $this->assertTrue($storage->load($admin->id())->isActive());

    $this->assertTrue($storage->load(1)->isBlocked());
    ncms_ui_cron();
    $storage->resetCache();
    $this->assertTrue($storage->load(1)->isActive());
  }

  /**
   * Tests saving both role limits without changing warning settings.
   */
  public function testSettingsForm(): void {
    $settings = $this->config('user_expire.settings')->getRawData();
    $form_state = new FormState();
    $form_object = UserExpireSettingsForm::create($this->container);
    $form = $form_object->buildForm([], $form_state);
    $warnings = $form['warnings'];

    ncms_ui_form_user_expire_form_alter($form, $form_state);

    $this->assertEmpty($form['user_expire_roles']['user_expire_administrator']['#states']['enabled'] ?? NULL);
    $this->assertArrayNotHasKey('authenticated_notice', $form['user_expire_roles']);
    $this->assertSame($warnings, $form['warnings']);
    $this->assertSame(31536000, $form['user_expire_roles']['user_expire_authenticated']['#default_value']);
    $this->assertSame(15552000, $form['user_expire_roles']['user_expire_administrator']['#default_value']);

    $form_state->setValues([
      'current_roles' => $form['current_roles']['#value'],
      'user_expire_authenticated' => '31536000',
      'user_expire_administrator' => '15552000',
      'frequency' => (string) $settings['frequency'],
      'offset' => (string) $settings['offset'],
      'send_expiration_warnings' => $settings['send_expiration_warnings'],
      'expiration_warning_mail_subject' => $settings['expiration_warning_mail']['subject'],
      'expiration_warning_mail_body' => $settings['expiration_warning_mail']['body'],
      'expiration_date_format' => 'F j, Y',
    ]);
    $form_object->validateForm($form, $form_state);
    $this->assertSame([], $form_state->getErrors());
    $form_object->submitForm($form, $form_state);

    $saved = $this->config('user_expire.settings')->getRawData();
    $this->assertSame($settings['user_expire_roles'], $saved['user_expire_roles']);
    foreach (['frequency', 'offset', 'send_expiration_warnings', 'expiration_warning_mail'] as $key) {
      $this->assertSame($settings[$key], $saved[$key]);
    }
  }

}
