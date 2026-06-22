<?php

namespace Drupal\Tests\ncms_tags\Unit\Plugin\views\filter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ncms_tags\CommonTaxonomyService;
use Drupal\ncms_tags\Plugin\views\filter\ComputedTagsActiveTagsFilter;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the computed tags active tags Views filter.
 */
#[Group('ncms_tags')]
class ComputedTagsActiveTagsFilterTest extends UnitTestCase {

  /**
   * Tests that the active-tags JSON payload is converted for Views.
   */
  public function testElementValidateFormatsActiveTagsPayload(): void {
    $element = ['#parents' => ['filters', 'computed_tags']];
    $form_state = (new FormState())->setValues([
      'filters' => [
        'computed_tags' => Json::encode([
          [
            'entity_id' => 12,
            'label' => 'Sudan',
          ],
          [
            'entity_id' => 34,
            'label' => 'Food Security',
          ],
        ]),
      ],
    ]);

    ComputedTagsActiveTagsFilter::elementValidate($element, $form_state);

    $this->assertSame([
      [
        'target_id' => 12,
        'label' => 'Sudan',
      ],
      [
        'target_id' => 34,
        'label' => 'Food Security',
      ],
    ], $form_state->getValue($element['#parents']));
  }

  /**
   * Tests that empty active-tags input is left untouched.
   */
  public function testElementValidateIgnoresEmptyPayload(): void {
    $element = ['#parents' => ['computed_tags']];
    $form_state = (new FormState())->setValues(['computed_tags' => '']);

    ComputedTagsActiveTagsFilter::elementValidate($element, $form_state);

    $this->assertSame('', $form_state->getValue($element['#parents']));
  }

  /**
   * Tests that the autocomplete input can exceed Drupal's default length.
   */
  public function testAfterBuildRemovesDefaultMaxlength(): void {
    $element = [
      '#type' => 'entity_autocomplete_active_tags',
      '#maxlength' => 128,
    ];

    $element = ComputedTagsActiveTagsFilter::afterBuild($element, new FormState());

    $this->assertSame('entity_autocomplete_active_tags', $element['#type']);
    $this->assertArrayNotHasKey('#maxlength', $element);
  }

  /**
   * Tests that the admin summary only appears for exposed filters.
   */
  public function testAdminSummaryOnlyShowsForExposedFilter(): void {
    $filter = $this->createFilter();
    $filter->setStringTranslation($this->getStringTranslationStub());

    $filter->options = ['exposed' => TRUE];
    $this->assertSame('exposed', (string) $filter->adminSummary());

    $filter->options = ['exposed' => FALSE];
    $this->assertNull($filter->adminSummary());
  }

  /**
   * Tests that the value form only adds the active-tags element when exposed.
   */
  public function testValueFormBuildsExposedActiveTagsElement(): void {
    $filter = new class([], 'computed_tags_active_tags', []) extends ComputedTagsActiveTagsFilter {

      /**
       * Public wrapper around the protected Views value form hook.
       */
      public function valueFormForTest(array &$form, FormStateInterface $form_state): void {
        $this->valueForm($form, $form_state);
      }

    };

    $form = [];
    $filter->valueFormForTest($form, (new FormState())->set('exposed', FALSE));
    $this->assertArrayNotHasKey('value', $form);

    $form = [];
    $filter->valueFormForTest($form, (new FormState())->set('exposed', TRUE));

    $value = $form['value'];
    $this->assertSame('entity_autocomplete_active_tags', $value['#type']);
    $this->assertSame('taxonomy_term', $value['#target_type']);
    $this->assertSame('tag_filter', $value['#selection_settings']['view']['view_name']);
    $this->assertSame('autocomplete_source', $value['#selection_settings']['view']['display_name']);
    $this->assertSame([[$filter, 'elementValidate']], $value['#element_validate']);
    $this->assertSame([[ComputedTagsActiveTagsFilter::class, 'afterBuild']], $value['#after_build']);
    $this->assertSame(['computed-tags-filter'], $value['#attributes']['class']);
    $this->assertSame(['ncms_tags/input.computed_tags'], $value['#attached']['library']);
  }

  /**
   * Tests that the filter adds field-table conditions for supported tags.
   */
  public function testQueryAddsConditionsForCommonTaxonomyTerms(): void {
    $terms = [
      10 => $this->mockTerm(10, 'country'),
      20 => $this->mockTerm(20, 'theme'),
      30 => $this->mockTerm(30, 'unknown'),
      40 => $this->mockTerm(40, 'year'),
    ];
    $filter = $this->createFilter($this->mockTermStorage([10, 20, 30, 40], $terms));
    $filter->value = [
      ['target_id' => 10],
      ['target_id' => 20],
      ['target_id' => 30],
      ['target_id' => 40],
    ];
    $filter->options = ['group' => 2];
    $filter->table = 'node__field_computed_tags';

    // The real Views query object is large; the filter only needs this tiny
    // add-table/add-where contract to build its computed tag conditions.
    $query = $this->createQueryRecorder([
      'node__field_country' => 'field_country_alias',
      'node__field_theme' => 'field_theme_alias',
      'node__field_year' => FALSE,
    ]);
    $filter->query = $query;

    $filter->query();

    $this->assertSame([
      ['node__field_computed_tags', NULL],
    ], $query->ensuredTables);
    $this->assertSame([
      'node__field_country',
      'node__field_theme',
      'node__field_year',
    ], $query->addedTables);
    $this->assertSame([
      [2, 'field_country_alias.field_country_target_id', 10],
      [2, 'field_theme_alias.field_theme_target_id', 20],
    ], $query->whereConditions);
  }

  /**
   * Tests that empty filter values do not alter the Views query.
   */
  public function testQueryIgnoresEmptyFilterValue(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->never())->method('loadMultiple');

    $filter = $this->createFilter($storage);
    $filter->value = [];
    $filter->query = $this->createQueryRecorder();

    $filter->query();

    $this->assertSame([], $filter->query->ensuredTables);
  }

  /**
   * Creates the filter with mocked services from the container.
   */
  private function createFilter(?EntityStorageInterface $taxonomy_storage = NULL): ComputedTagsActiveTagsFilter {
    $taxonomy_storage ??= $this->createMock(EntityStorageInterface::class);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($taxonomy_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('ncms_tags.common_taxonomies', new CommonTaxonomyService());

    return ComputedTagsActiveTagsFilter::create($container, [], 'computed_tags_active_tags', []);
  }

  /**
   * Mocks term storage and asserts which term IDs are loaded by the filter.
   *
   * @param int[] $expected_ids
   *   The term IDs expected to be passed to loadMultiple().
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The loaded terms to return.
   */
  private function mockTermStorage(array $expected_ids, array $terms): EntityStorageInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with($expected_ids)
      ->willReturn($terms);
    return $storage;
  }

  /**
   * Mocks a taxonomy term with the fields used by the filter.
   */
  private function mockTerm(int $id, string $bundle): TermInterface {
    $term = $this->createMock(TermInterface::class);
    $term->method('id')->willReturn($id);
    $term->method('bundle')->willReturn($bundle);
    return $term;
  }

  /**
   * Creates a recorder for the small Views query API used by the filter.
   *
   * @param array<string, string|false> $table_aliases
   *   Table aliases keyed by table name. Use FALSE to simulate addTable()
   *   failing to add a table.
   */
  private function createQueryRecorder(array $table_aliases = []): object {
    return new class($table_aliases) {

      /**
       * Tables requested through ensureTable().
       *
       * @var array<int, array{0: string, 1: string|null}>
       */
      public array $ensuredTables = [];

      /**
       * Tables requested through addTable().
       *
       * @var string[]
       */
      public array $addedTables = [];

      /**
       * Where conditions added by the filter.
       *
       * @var array<int, array{0: int|string, 1: string, 2: int|string}>
       */
      public array $whereConditions = [];

      /**
       * Constructs the query recorder.
       *
       * @param array<string, string|false> $tableAliases
       *   Table aliases keyed by table name.
       */
      public function __construct(private readonly array $tableAliases) {
      }

      /**
       * Records the base table ensured by HandlerBase::ensureMyTable().
       */
      public function ensureTable(string $table, ?string $relationship = NULL): string {
        $this->ensuredTables[] = [$table, $relationship];
        return $table . '_alias';
      }

      /**
       * Records a field table join and returns the configured alias.
       */
      public function addTable(string $table): string|false {
        $this->addedTables[] = $table;
        return $this->tableAliases[$table] ?? $table . '_alias';
      }

      /**
       * Records the condition that would be added to the Views query.
       */
      public function addWhere(int|string $group, string $field, int|string $value): void {
        $this->whereConditions[] = [$group, $field, $value];
      }

    };
  }

}
