<?php

namespace Drupal\Tests\ncms_graphql\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\user\Entity\User;

/**
 * Test class for the HidUser data producer.
 *
 * @group ncms_graphql
 */
class HidUserTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ncms_graphql',
    'social_auth',
    'social_auth_hid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $anonymous = User::load(0);
    $this->setCurrentUser($anonymous);
  }

  /**
   * Test the HID user data producer.
   *
   * @covers \Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\HidUser
   */
  public function testHidUser(): void {
    $result = $this->executeDataProducer('hid_user');
    $this->assertInstanceOf('Drupal\Core\Session\AccountProxy', $result);
    /** @var \Drupal\Core\Session\AccountProxy $result */
    $this->assertEquals(TRUE, $result->isAnonymous());
  }

}
