<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\ncms_ui\Entity\EntityCompare;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\ckeditor5\Traits\PrivateMethodUnitTestTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the ncms_ui.entity_compare service.
 */
class EntityCompareTest extends UnitTestCase {

  use PrivateMethodUnitTestTrait;

  /**
   * Data provider for testHasChanged.
   */
  public static function dataProviderHasChanged() {
    $test_cases = [];

    $entity_data = self::getEntityDataArray();
    $paragraph_data = self::getParagraphDataArray();

    $test_cases['equal'] = [
      'updated' => [
        'entity_data' => ['status' => [['value' => TRUE]]] + $entity_data,
        'paragraph_data' => $paragraph_data,
      ],
      'original' => [
        'entity_data' => ['status' => [['value' => TRUE]]] + $entity_data,
        'paragraph_data' => $paragraph_data,
      ],
      'expected' => FALSE,
    ];
    $test_cases['changed_entity_status'] = [
      'updated' => [
        'entity_data' => ['status' => [['value' => TRUE]]] + $entity_data,
        'paragraph_data' => $paragraph_data,
      ],
      'original' => [
        'entity_data' => ['status' => [['value' => FALSE]]] + $entity_data,
        'paragraph_data' => $paragraph_data,
      ],
      'expected' => TRUE,
    ];

    $test_cases['changed_paragraph_status'] = [
      'updated' => [
        'entity_data' => $entity_data,
        'paragraph_data' => ['status' => [['value' => TRUE]]] + $paragraph_data,
      ],
      'original' => [
        'entity_data' => $entity_data,
        'paragraph_data' => ['status' => [['value' => FALSE]]] + $paragraph_data,
      ],
      'expected' => TRUE,
    ];

    return $test_cases;
  }

  /**
   * Test the ::hasChanged method.
   */
  #[DataProvider('dataProviderHasChanged')]
  public function testHasChanged($updated, $original, $expected) {
    $updated_entity = $this->getEntityProphecyWithData($updated['entity_data'], $updated['paragraph_data'])->reveal();
    $original_entity = $this->getEntityProphecyWithData($original['entity_data'], $original['paragraph_data'])->reveal();
    $entity_compare = new EntityCompare();
    $this->assertEquals($expected, $entity_compare->hasChanged($updated_entity, $original_entity));
  }

  /**
   * Test the hashing of entities.
   */
  public function testHashEntity() {
    $entity_compare = new EntityCompare();
    $method = self::getMethod(EntityCompare::class, 'hashEntity');

    $entity_data = $this->getEntityDataArray();

    // The original entity.
    $entity_1 = $this->getEntityProphecyWithData($entity_data);
    $hash_1 = $method->invokeArgs($entity_compare, [$entity_1->reveal()]);

    // Now unset a couple of array keys, which should not affect the hash.
    $entity_2 = $this->getEntityProphecyWithData(array_diff_key($entity_data, array_flip([
      'vid', 'changed', 'revision_timestamp', 'revision_uid',
    ])));
    $hash_2 = $method->invokeArgs($entity_compare, [$entity_2->reveal()]);

    // These 2 should be equal.
    $this->assertEquals($hash_1, $hash_2);

    // Now unset an array key that affects the hash.
    $entity_3 = $this->getEntityProphecyWithData(array_diff_key($entity_data, array_flip([
      'status',
    ])));
    $hash_3 = $method->invokeArgs($entity_compare, [$entity_3->reveal()]);

    // These should not be equal.
    $this->assertNotEquals($hash_2, $hash_3);
  }

  /**
   * Data provider for testHashEntity.
   */
  public static function dataBuildHashableEntityData() {
    $test_cases = [];
    $test_cases['promoted_paragraph'] = [
      'entity_data' => [
        'paragraph' => [
          'title' => 'Paragraph title',
          'promoted_behavior' => ['promoted' => TRUE],
        ],
      ],
      'expected' => [
        'title' => 'Paragraph title',
        'promoted_behavior' => [
          'promoted' => TRUE,
        ],
      ],
    ];
    $test_cases['unpromoted_paragraph'] = [
      'entity_data' => [
        'paragraph' => [
          'title' => 'Paragraph title',
          'promoted_behavior' => ['promoted' => FALSE],
        ],
      ],
      'expected' => [
        'title' => 'Paragraph title',
      ],
    ];
    $entity_data = self::getEntityDataArray();
    $test_cases['unset_entity_keys'] = [
      'entity_data' => [
        'entity' => $entity_data,
      ],
      'expected' => array_diff_key($entity_data, array_flip([
        'vid', 'changed', 'revision_timestamp', 'revision_uid', 'revision_log',
      ])),
    ];
    return $test_cases;
  }

  /**
   * Test the building of hashable entity data.
   */
  #[DataProvider('dataBuildHashableEntityData')]
  public function testBuildHashableEntityData(array $entity_data, $expected) {
    $entity_compare = new EntityCompare();
    $method = self::getMethod(EntityCompare::class, 'buildHashableEntityData');

    if (!empty($entity_data['paragraph'])) {
      $entity = $this->getParagraphProphecyWithData($entity_data['paragraph']);
    }
    elseif (!empty($entity_data['entity'])) {
      $entity = $this->getEntityProphecyWithData($entity_data['entity']);
    }

    $array = $method->invokeArgs($entity_compare, [$entity->reveal()]);
    $this->assertEquals($expected, $array);
  }

  /**
   * Data provider for testReduceArray.
   */
  public static function dataProviderReduceArray() {
    $test_cases = [];
    $test_cases[] = [
      'array' => [
        0 => 1,
        1 => 0,
      ],
      'remove_keys' => [],
      'expected' => [0 => 1],
    ];
    $test_cases[] = [
      'array' => [
        0 => 1,
        1 => 1,
      ],
      'remove_keys' => [0],
      'expected' => [1 => 1],
    ];
    $test_cases[] = [
      'array' => [
        0 => 1,
        1 => [
          0 => 1,
          1 => 0,
        ],
      ],
      'remove_keys' => [],
      'expected' => [
        0 => 1,
        1 => [
          0 => 1,
        ],
      ],
    ];
    $test_cases[] = [
      'array' => [
        0 => 1,
        1 => [
          0 => 1,
          1 => 0,
        ],
      ],
      'remove_keys' => [0],
      'expected' => [],
    ];
    $test_cases[] = [
      'array' => [
        0 => 1,
        1 => [
          0 => 1,
          1 => [
            0 => 0,
            1 => 1,
          ],
        ],
      ],
      'remove_keys' => [],
      'expected' => [
        0 => 1,
        1 => [
          0 => 1,
          1 => [
            1 => 1,
          ],
        ],
      ],
    ];
    $test_cases[] = [
      'array' => [0 => 1, 1 => (object) []],
      'remove_keys' => [],
      'expected' => [0 => 1],
    ];

    $entity = new ArrayTestClass([
      'name' => 'Michael',
      'profession' => 'basketball player',
      'status' => 0,
    ]);
    $test_cases[] = [
      'array' => [0 => 1, 1 => $entity],
      'remove_keys' => [],
      'expected' => [
        0 => 1,
        1 => [
          'name' => 'Michael',
          'profession' => 'basketball player',
        ],
      ],
    ];
    $test_cases[] = [
      'array' => [0 => 1, 'profession' => 'test', 1 => $entity],
      'remove_keys' => ['profession', 0],
      'expected' => [1 => ['name' => 'Michael']],
    ];
    return $test_cases;
  }

  /**
   * Test the reduction of arrays.
   */
  #[DataProvider('dataProviderReduceArray')]
  public function testReduceArray(array $array, array $remove_keys, array $expected) {
    $entity_compare = new EntityCompare();
    $method = self::getMethod(EntityCompare::class, 'reduceArray');
    $method->invokeArgs($entity_compare, [&$array, $remove_keys]);
    $this->assertEquals($expected, $array);
  }

  /**
   * Get an entity prophecy with the given data.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The entity prohpecy.
   */
  private function getEntityProphecyWithData(array $entity_data, array $paragraph_data = []) {
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->toArray()->willReturn($entity_data);
    $paragraph = !empty($paragraph_data) ? $this->getParagraphProphecyWithData($paragraph_data)->reveal() : NULL;
    $entity_reference_field_item_list = $this->prophesize(EntityReferenceFieldItemList::class);
    $entity_reference_field_item_list->referencedEntities()->willReturn(array_filter([$paragraph]));
    $entity->get('field_paragraphs')->willReturn($entity_reference_field_item_list);
    return $entity;
  }

  /**
   * Get a paragraph prophecy with the given data.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The paragraph prohpecy.
   */
  private function getParagraphProphecyWithData($paragraph_data) {
    $behavior_settings = [];
    if (!empty($paragraph_data['promoted_behavior'])) {
      $behavior_settings = ['promoted_behavior' => $paragraph_data['promoted_behavior']];
      unset($paragraph_data['promoted_behavior']);
    }
    $paragraph = $this->prophesize(Paragraph::class);
    $paragraph->toArray()->willReturn($paragraph_data);
    $paragraph->getAllBehaviorSettings()->willReturn($behavior_settings);
    return $paragraph;
  }

  /**
   * Get an array representing a paragraph.
   *
   * @param array $values
   *   An array of values to set instead of the default values.
   * @param array $promoted
   *   Whether the paragraph should be promoted or not.
   */
  private static function getParagraphDataArray(array $values = [], $promoted = FALSE) {
    return $values + [
      'id' => [['value' => '4556']],
      'uuid' => [['value' => '3005ed14-314c-4418-a5f8-f78cfa2240d2']],
      'revision_id' => [['value' => '46469']],
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_type']],
      'status' => [['value' => 1]],
      'created' => [['value' => '1711100101']],
      'parent_id' => [['value' => '295']],
      'parent_type' => [['value' => 'node']],
      'parent_field_name' => [['value' => 'field_paragraphs']],
      'default_langcode' => [['value' => '1']],
      'revision_default' => [['value' => '1']],
      'revision_translation_affected' => [['value' => TRUE]],
    ];
  }

  /**
   * Get an array representing an entity.
   *
   * @param array $values
   *   An array of values to set instead of the default values.
   */
  private static function getEntityDataArray(array $values = []) {
    return $values + [
      'nid' => [['value' => '1']],
      'uuid' => [['value' => '9870e8e8-7e3a-4db0-9453-a3265b6b706a']],
      'vid' => [],
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'article']],
      'revision_timestamp' => [['value' => 1711100507]],
      'revision_uid' => [['target_id' => '2']],
      'revision_log' => [],
      'status' => [['value' => TRUE]],
      'uid' => [['target_id' => '3']],
      'title' => [['value' => 'A title for the content']],
      'created' => [['value' => 1692342592]],
      'changed' => [['value' => 1711100250]],
      'field_paragraphs' => [['target_id' => '4', 'target_revision_id' => '5']],
    ];
  }

}

/**
 * Test class providing a toArray method.
 */
class ArrayTestClass {

  /**
   * Store the array data.
   *
   * @var array
   */
  private array $data;

  /**
   * Public constructor.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Return the array.
   *
   * @return array
   *   The array.
   */
  public function toArray(): array {
    return $this->data;
  }

}
