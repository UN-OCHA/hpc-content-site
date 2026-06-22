<?php

namespace Drupal\Tests\ncms_ui\Unit\Autocomplete;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\ncms_ui\Autocomplete\EntityAutocompleteMatcher;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the NCMS entity autocomplete matcher.
 */
#[Group('ncms_ui')]
class EntityAutocompleteMatcherTest extends UnitTestCase {

  /**
   * Tests that matches include sanitized values and NCMS metadata.
   */
  public function testGetMatchesAddsContentSpaceAndUpdatedMetadata(): void {
    $this->setUpStringTranslation();

    $entity = $this->mockContentSpaceAwareChangedEntity();
    $selection_manager = $this->createMock(SelectionPluginManagerInterface::class);
    $selection_manager->expects($this->once())
      ->method('getInstance')
      ->with([
        'match_operator' => 'STARTS_WITH',
        'target_type' => 'node',
        'handler' => 'default',
      ])
      ->willReturn($this->mockSelectionHandler());

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(123)->willReturn($entity);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $entity_repository = $this->createMock(EntityRepositoryInterface::class);
    $entity_repository->method('getTranslationFromContext')->with($entity)->willReturn($entity);

    $content_space_manager = $this->getMockBuilder(ContentSpaceManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getCurrentContentSpaceId'])
      ->getMock();
    $content_space_manager->method('getCurrentContentSpaceId')->willReturn(1);

    $date_formatter = $this->createMock(DateFormatterInterface::class);
    $date_formatter->method('format')->with(1700000000, 'short')->willReturn('14 Nov 2023');

    $matcher = new EntityAutocompleteMatcher(
      $selection_manager,
      $content_space_manager,
      $date_formatter,
      $entity_type_manager,
      $entity_repository
    );

    $this->assertSame([
      [
        'value' => Tags::encode('Sudan, Plan (123)'),
        'label' => 'Sudan, Plan [<i>Other Space, last updated 14 Nov 2023</i>]',
      ],
    ], $matcher->getMatches('node', 'default', ['match_operator' => 'STARTS_WITH'], 'Sud'));
  }

  /**
   * Sets up string translation for TranslatableMarkup metadata.
   */
  private function setUpStringTranslation(): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Mocks the selection handler output returned by core autocomplete matching.
   */
  private function mockSelectionHandler(): SelectionInterface {
    $handler = $this->createMock(SelectionInterface::class);
    $handler->expects($this->once())
      ->method('getReferenceableEntities')
      ->with('Sud', 'STARTS_WITH', 10)
      ->willReturn([
        'article' => [
          123 => 'Sudan, Plan',
        ],
      ]);
    return $handler;
  }

  /**
   * Mocks an entity carrying both NCMS content-space and changed metadata.
   */
  private function mockContentSpaceAwareChangedEntity(): ContentSpaceAwareInterface&EntityChangedInterface {
    $content_space = $this->createMock(EntityInterface::class);
    $content_space->method('id')->willReturn(2);
    $content_space->method('label')->willReturn('Other Space');

    $entity = $this->createMockForIntersectionOfInterfaces([
      ContentSpaceAwareInterface::class,
      EntityChangedInterface::class,
    ]);
    $entity->method('getContentSpace')->willReturn($content_space);
    $entity->method('getChangedTime')->willReturn(1700000000);
    return $entity;
  }

}
