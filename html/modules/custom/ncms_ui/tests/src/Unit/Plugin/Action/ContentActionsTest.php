<?php

namespace Drupal\Tests\ncms_ui\Unit\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Storage\ContentStorage;
use Drupal\ncms_ui\Plugin\Action\MoveToTrash;
use Drupal\ncms_ui\Plugin\Action\Publish;
use Drupal\ncms_ui\Plugin\Action\Unpublish;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the content entity action plugins.
 */
#[Group('ncms_ui')]
class ContentActionsTest extends UnitTestCase {

  /**
   * Tests that invalid action targets return the requested access type.
   */
  public function testAccessDeniesInvalidTargets(): void {
    $action = new MoveToTrash([], 'content_entity_move_to_trash', []);
    $account = $this->createMock(AccountInterface::class);

    $this->assertFalse($action->access(new \stdClass(), $account));
    $this->assertFalse($action->access(new \stdClass(), $account, TRUE)->isAllowed());
  }

  /**
   * Tests that valid targets delegate to the node update access result.
   */
  public function testAccessDelegatesToContentUpdateAccess(): void {
    $action = new MoveToTrash([], 'content_entity_move_to_trash', []);
    $account = $this->createMock(AccountInterface::class);
    $node = $this->createContentNodeMock(['access']);

    $node->expects($this->exactly(2))
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn(AccessResult::allowed());

    $this->assertTrue($action->access($node, $account));
    $this->assertTrue($action->access($node, $account, TRUE)->isAllowed());
  }

  /**
   * Tests that the move-to-trash action marks and saves content entities.
   */
  public function testMoveToTrashExecutesOnContentEntities(): void {
    $this->setUpCacheTagsInvalidator(['node:1']);

    $node = $this->createContentNodeMock(['setDeleted', 'save', 'getCacheTags']);
    $node->expects($this->once())->method('setDeleted');
    $node->expects($this->once())->method('save');
    $node->method('getCacheTags')->willReturn(['node:1']);

    $action = new MoveToTrash([], 'content_entity_move_to_trash', []);
    $action->execute($node);
  }

  /**
   * Tests that the publish action updates the current revision to published.
   */
  public function testPublishExecutesOnContentEntities(): void {
    $this->setUpEntityTypeManager(NodeInterface::PUBLISHED);
    $this->setUpCacheTagsInvalidator(['node:1']);

    $node = $this->createContentNodeMock(['getCacheTags']);
    $node->method('getCacheTags')->willReturn(['node:1']);

    $action = new Publish([], 'content_entity_publish', []);
    $action->execute($node);
  }

  /**
   * Tests unpublishing the current revision.
   */
  public function testUnpublishExecutesOnContentEntities(): void {
    $this->setUpEntityTypeManager(NodeInterface::NOT_PUBLISHED);
    $this->setUpCacheTagsInvalidator(['node:1']);

    $node = $this->createContentNodeMock(['getCacheTags']);
    $node->method('getCacheTags')->willReturn(['node:1']);

    $action = new Unpublish([], 'content_entity_unpublish', []);
    $action->execute($node);
  }

  /**
   * Creates an Article mock so the object passes both required interfaces.
   *
   * @param string[] $methods
   *   Methods to override on the mock.
   */
  private function createContentNodeMock(array $methods): Article {
    return $this->getMockBuilder(Article::class)
      ->disableOriginalConstructor()
      ->onlyMethods($methods)
      ->getMock();
  }

  /**
   * Sets up node storage and expects the requested revision status update.
   */
  private function setUpEntityTypeManager(int $expected_status): void {
    $node_storage = $this->getMockBuilder(ContentStorage::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['updateRevisionStatus'])
      ->getMock();
    $node_storage->expects($this->once())
      ->method('updateRevisionStatus')
      ->with($this->isInstanceOf(Article::class), $expected_status);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);

    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Sets up cache tag invalidation expected from action execution.
   *
   * @param string[] $expected_tags
   *   Cache tags expected to be invalidated.
   */
  private function setUpCacheTagsInvalidator(array $expected_tags): void {
    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($expected_tags);

    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();
    $container->set('cache_tags.invalidator', $invalidator);
    \Drupal::setContainer($container);
  }

}
