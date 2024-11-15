<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\ncms_graphql\Wrappers\QueryConnection;
use Drupal\node\NodeInterface;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load all exportable articles.
 *
 * @DataProducer(
 *   id = "article_export",
 *   name = @Translation("Load all exportable articles"),
 *   description = @Translation("Loads all exportable articles."),
 *   produces = @ContextDefinition("entities",
 *     label = @Translation("Entities")
 *   ),
 *   consumes = {
 *     "tags" = @ContextDefinition("string",
 *       label = @Translation("Tags"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class ArticleExport extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Resolver.
   *
   * @param array $tags
   *   The tags to search for.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   A context object.
   *
   * @return \GraphQL\Deferred
   *   A promise.
   */
  public function resolve(array $tags = NULL, FieldContext $context) {
    return new Deferred(function () use ($tags, $context) {
      $type = 'node';

      // Add the list cache tags so that the cache entry is purged whenever a
      // new entity of this type is saved.
      $entity_type = $this->entityTypeManager->getDefinition($type);
      $context->addCacheTags($entity_type->getListCacheTags());
      $context->addCacheContexts($entity_type->getListCacheContexts());

      // Load the buffered entities.
      $query = $this->entityTypeManager
        ->getStorage($type)
        ->getQuery();
      $query->accessCheck(TRUE);
      $query->condition('type', 'article');
      $query->condition('status', NodeInterface::PUBLISHED);
      $query->condition('field_tags', NULL, 'IS NOT NULL');
      $query->condition('field_content_space', NULL, 'IS NOT NULL');
      $query->sort('changed', 'DESC');
      if (!empty($tags)) {
        // Add conditions to limit to specific tags, either on the article
        // itself or on the referenced content space.
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
          'vid' => 'major_tags',
          'name' => $tags,
        ]);
        $tag_ids = array_keys($terms);
        foreach ($tag_ids as $tag_id) {
          $and_condition = $query->andConditionGroup();
          $or_condition = $query->orConditionGroup();
          $or_condition->condition('field_content_space.entity.field_major_tags', $tag_id);
          $or_condition->condition('field_tags', $tag_id);
          $and_condition->condition($or_condition);
          $query->condition($and_condition);
        }
      }
      return new QueryConnection($query);
    });
  }

}
