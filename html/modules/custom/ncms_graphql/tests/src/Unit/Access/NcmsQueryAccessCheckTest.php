<?php

namespace Drupal\Tests\ncms_graphql\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Access\QueryAccessCheck;
use Drupal\graphql\Entity\Server;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\ncms_graphql\Access\NcmsQueryAccessCheck;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the NCMS GraphQL query access check.
 */
#[Group('ncms_graphql')]
class NcmsQueryAccessCheckTest extends UnitTestCase {

  /**
   * Tests that bypass permission grants access before the decorated checker.
   */
  public function testBypassPermissionAllowsAccess(): void {
    $decorated = $this->createMock(QueryAccessCheck::class);
    $decorated->expects($this->never())->method('access');

    $access_check = new NcmsQueryAccessCheck($decorated, new RequestStack());
    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(TRUE), $this->createServer('ncms_schema'));

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests that non-NCMS schemas keep the decorated access result.
   */
  public function testNonNcmsSchemaReturnsDecoratedResult(): void {
    $decorated = $this->createMock(QueryAccessCheck::class);
    $decorated->method('access')->willReturn(AccessResult::forbidden('decorated'));

    $access_check = new NcmsQueryAccessCheck($decorated, new RequestStack());
    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(FALSE), $this->createServer('default'));

    $this->assertTrue($result->isForbidden());
    $this->assertSame('decorated', $result->getReason());
  }

  /**
   * Tests that an allowed decorated result is preserved.
   */
  public function testDecoratedAllowedResultIsPreserved(): void {
    $decorated = $this->createMock(QueryAccessCheck::class);
    $decorated->method('access')->willReturn(AccessResult::allowed());

    $access_check = new NcmsQueryAccessCheck($decorated, new RequestStack());
    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(FALSE), $this->createServer('ncms_schema'));

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests NCMS access-key validation.
   */
  public function testRequiredAccessKey(): void {
    $decorated = $this->createMock(QueryAccessCheck::class);
    $decorated->method('access')->willReturn(AccessResult::forbidden());

    // The decorated checker is denied so the NCMS access-key override path is
    // the only way this request can become allowed.
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/', cookies: ['access_key' => 'expected']));
    $access_check = new NcmsQueryAccessCheck($decorated, $request_stack);

    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(FALSE), $this->createServer('ncms_schema', [
      'require_access_key' => TRUE,
      'access_key' => 'expected',
    ]));
    $this->assertTrue($result->isAllowed());

    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/', cookies: ['access_key' => 'wrong']));
    $access_check = new NcmsQueryAccessCheck($decorated, $request_stack);
    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(FALSE), $this->createServer('ncms_schema', [
      'require_access_key' => TRUE,
      'access_key' => 'expected',
    ]));
    $this->assertTrue($result->isForbidden());
    $this->assertSame('Invalid access key given', $result->getReason());

    $result = $access_check->access($this->mockAccountWithGraphqlBypassPermission(FALSE), $this->createServer('ncms_schema', [
      'require_access_key' => TRUE,
    ]));
    $this->assertTrue($result->isForbidden());
    $this->assertSame('No access key set', $result->getReason());
  }

  /**
   * Mocks an account with optional GraphQL bypass permission.
   */
  private function mockAccountWithGraphqlBypassPermission(bool $has_bypass_permission): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->with('bypass graphql access')->willReturn($has_bypass_permission);
    return $account;
  }

  /**
   * Creates a GraphQL server config entity with schema configuration.
   *
   * Using the real config entity keeps public property access aligned with the
   * production access check.
   */
  private function createServer(string $schema, array $schema_configuration = []): ServerInterface {
    return new Server([
      'name' => 'test_server',
      'label' => 'Test server',
      'schema' => $schema,
      'schema_configuration' => [
        $schema => $schema_configuration,
      ],
    ], 'graphql_server');
  }

}
