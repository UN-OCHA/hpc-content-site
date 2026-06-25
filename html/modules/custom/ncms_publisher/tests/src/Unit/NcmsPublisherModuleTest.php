<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ncms_publisher\PublisherRefreshNotifier;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests refresh notification hooks.
 */
#[Group('ncms_publisher')]
class NcmsPublisherModuleTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    include_once __DIR__ . '/../../../ncms_publisher.module';
  }

  /**
   * Tests that trashed content is queued even with a published revision.
   */
  public function testEntityUpdateQueuesTrashedContent(): void {
    $entity = $this->createContentEntity(FALSE, TRUE, TRUE);

    $refresh_notifier = $this->createMock(PublisherRefreshNotifier::class);
    $refresh_notifier->expects($this->once())
      ->method('enqueue')
      ->with($entity, 'trashed');
    $this->setRefreshNotifier($refresh_notifier);

    ncms_publisher_entity_update($entity);
  }

  /**
   * Tests that unpublished drafts with a published revision are not queued.
   */
  public function testEntityUpdateSkipsUnpublishedDrafts(): void {
    $entity = $this->createContentEntity(FALSE, FALSE, TRUE);

    $refresh_notifier = $this->createMock(PublisherRefreshNotifier::class);
    $refresh_notifier->expects($this->never())->method('enqueue');
    $this->setRefreshNotifier($refresh_notifier);

    ncms_publisher_entity_update($entity);
  }

  /**
   * Tests that deleted content is queued with the deleted event.
   */
  public function testEntityDeleteQueuesDeletedContent(): void {
    $entity = $this->createContentEntity(FALSE, TRUE, TRUE);

    $refresh_notifier = $this->createMock(PublisherRefreshNotifier::class);
    $refresh_notifier->expects($this->once())
      ->method('enqueue')
      ->with($entity, 'deleted');
    $this->setRefreshNotifier($refresh_notifier);

    ncms_publisher_entity_delete($entity);
  }

  /**
   * Creates a content entity mock.
   *
   * @param bool $published
   *   Whether the entity is published.
   * @param bool $deleted
   *   Whether the entity is deleted.
   * @param bool $has_published_revision
   *   Whether the entity has a previously published revision.
   *
   * @return \Drupal\ncms_ui\Entity\ContentInterface
   *   The content entity mock.
   */
  private function createContentEntity(bool $published, bool $deleted, bool $has_published_revision): ContentInterface {
    $entity = $this->createMock(ContentInterface::class);
    $entity->method('bundle')->willReturn('article');
    $entity->method('isPublished')->willReturn($published);
    $entity->method('isDeleted')->willReturn($deleted);
    $entity->method('getLastPublishedRevision')->willReturn($has_published_revision ? $this->createMock(ContentInterface::class) : NULL);
    return $entity;
  }

  /**
   * Sets the refresh notifier service.
   *
   * @param \Drupal\ncms_publisher\PublisherRefreshNotifier $refresh_notifier
   *   The refresh notifier mock.
   */
  private function setRefreshNotifier(PublisherRefreshNotifier $refresh_notifier): void {
    $container = new ContainerBuilder();
    $container->set('ncms_publisher.refresh_notifier', $refresh_notifier);
    \Drupal::setContainer($container);
  }

}
