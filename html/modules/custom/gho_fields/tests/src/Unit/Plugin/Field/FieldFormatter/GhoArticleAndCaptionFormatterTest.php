<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoArticleListFormatter;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoCaptionFormatter;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests simple GHO field formatter render arrays.
 */
#[Group('gho_fields')]
class GhoArticleAndCaptionFormatterTest extends UnitTestCase {

  /**
   * Tests caption items are mapped to caption formatter render arrays.
   */
  public function testCaptionViewElements(): void {
    $formatter = new GhoCaptionFormatter(
      'gho_caption',
      [],
      $this->createMock(FieldDefinitionInterface::class),
      [],
      'hidden',
      'default',
      []
    );
    $items = $this->mockFieldItemList([
      (object) [
        'first' => 'Geneva',
        'second' => 'Operational update',
      ],
    ]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertSame('Geneva', $elements[0]['#location']);
    $this->assertSame('Operational update', $elements[0]['#caption']);
    $this->assertNull($elements[0]['#credits']);
    $this->assertInstanceOf(Attribute::class, $elements[0]['#attributes']);
    $this->assertSame('gho_caption_formatter', $elements[0]['#theme']);
  }

  /**
   * Tests article lists include accessible routed node links.
   */
  public function testArticleListViewElements(): void {
    $node = $this->fakeArticleListNode('Translated article', TRUE, TRUE);
    $untranslated_node = $this->fakeArticleListNode('Draft article', FALSE, FALSE);
    $denied_node = $this->fakeArticleListNode('Private article', TRUE, TRUE, FALSE);
    $formatter = $this->createArticleListFormatter([
      1 => $node,
      2 => $untranslated_node,
      3 => $denied_node,
    ]);
    $items = $this->mockFieldItemList([
      $this->mockLinkItem(Url::fromRoute('entity.node.canonical', ['node' => 1])),
      $this->mockLinkItem(Url::fromRoute('entity.node.canonical', ['node' => 2])),
      $this->mockLinkItem(Url::fromRoute('entity.node.canonical', ['node' => 3])),
      $this->mockLinkItem(Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => 7])),
      $this->mockLinkItem(Url::fromUri('https://example.org/external')),
    ]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertSame('gho_article_list_formatter', $elements['#theme']);
    $this->assertSame('Translated article', $elements['#links'][0]['title']);
    $this->assertSame('Draft article', $elements['#links'][1]['title']);
    $classes = (string) $elements['#links'][1]['attributes'];
    $this->assertStringContainsString('node--untranslated', $classes);
    $this->assertStringContainsString('node--unpublished', $classes);
    $this->assertCount(2, $elements['#links']);
  }

  /**
   * Tests no render array is returned when no item resolves to a node.
   */
  public function testArticleListViewElementsWithoutNodeLinks(): void {
    $formatter = $this->createArticleListFormatter([]);
    $items = $this->mockFieldItemList([
      $this->mockLinkItem(Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => 7])),
      $this->mockLinkItem(Url::fromUri('https://example.org/external')),
    ]);

    $this->assertSame([], $formatter->viewElements($items, 'en'));
  }

  /**
   * Creates an article-list formatter with node loading services.
   */
  private function createArticleListFormatter(array $nodes): GhoArticleListFormatter {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturnCallback(static fn (array $ids) => array_intersect_key($nodes, array_flip($ids)));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('fr');

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('language_manager', $language_manager);
    $container->set('current_user', $this->createMock(AccountInterface::class));

    $formatter = GhoArticleListFormatter::create($container, [
      'field_definition' => $this->createMock(FieldDefinitionInterface::class),
      'settings' => [],
      'label' => 'hidden',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ], 'gho_article_list', []);
    $formatter->setStringTranslation($this->getStringTranslationStub());
    return $formatter;
  }

  /**
   * Mocks an iterable field item list without invoking typed-data creation.
   */
  private function mockFieldItemList(array $items): FieldItemListInterface {
    $item_list = $this->createMockForIntersectionOfInterfaces([
      FieldItemListInterface::class,
      \IteratorAggregate::class,
    ]);
    $item_list->method('getIterator')->willReturn(new \ArrayIterator($items));
    return $item_list;
  }

  /**
   * Creates the node shape that GhoArticleListFormatter reads.
   */
  private function fakeArticleListNode(string $title, bool $published, bool $translated, bool $allowed = TRUE): object {
    return new class($title, $published, $translated, $allowed) {

      /**
       * The public title field shape expected by the formatter.
       */
      public object $title;

      /**
       * Constructs a node-shaped test double.
       */
      public function __construct(string $title, private readonly bool $published, private readonly bool $translated, private readonly bool $allowed) {
        $this->title = (object) [
          'value' => $title,
        ];
      }

      /**
       * Checks if the node has the requested translation.
       */
      public function hasTranslation(string $langcode): bool {
        return $this->translated;
      }

      /**
       * Gets the translated node.
       */
      public function getTranslation(string $langcode): object {
        return $this;
      }

      /**
       * Checks if the node is published.
       */
      public function isPublished(): bool {
        return $this->published;
      }

      /**
       * Checks if the current user can view the node.
       */
      public function access(string $operation, AccountInterface $account, bool $return_as_object): AccessResult {
        return $this->allowed ? AccessResult::allowed() : AccessResult::forbidden();
      }

    };
  }

  /**
   * Mocks a link item returning the supplied URL.
   */
  private function mockLinkItem(Url $url): LinkItemInterface {
    $item = $this->createMock(LinkItemInterface::class);
    $item->method('getUrl')->willReturn($url);
    return $item;
  }

}
