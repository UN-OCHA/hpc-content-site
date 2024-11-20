<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for content exports.
 */
abstract class ContentExportBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The common taxonomies service.
   *
   * @var \Drupal\ncms_tags\CommonTaxonomyService
   */
  protected $commonTaxonomies;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->commonTaxonomies = $container->get('ncms_tags.common_taxonomies');
    return $instance;
  }

  /**
   * Add tag conditions to an export query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The database query object.
   * @param string[] $tags
   *   The list of tags to search for.
   */
  protected function addTagConditionsToQuery(QueryInterface $query, $tags) {
    // Add conditions to limit to specific tags, either on the content node
    // itself or on the referenced content space.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $this->commonTaxonomies->getCommonTaxonomyBundles(),
      'name' => $tags,
    ]);
    $tag_ids = array_keys($terms);
    foreach ($tag_ids as $tag_id) {
      $and_condition = $query->andConditionGroup();
      $or_condition = $query->orConditionGroup();
      $or_condition->condition('field_content_space.entity.field_computed_tags', $tag_id);
      foreach ($this->commonTaxonomies->getCommonTaxonomyFieldNames() as $field_name) {
        $or_condition->condition($field_name, $tag_id);
      }
      $and_condition->condition($or_condition);
      $query->condition($and_condition);
    }
  }

}
