<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\node\NodeInterface;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load all exportable documents.
 *
 * @DataProducer(
 *   id = "article_document",
 *   name = @Translation("Load all exportable documents"),
 *   description = @Translation("Loads all exportable documents."),
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
class DocumentExport extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
      // Load the buffered entities.
      $query = $this->entityTypeManager
        ->getStorage($type)
        ->getQuery();
      $query->condition('type', 'document');
      $query->condition('status', NodeInterface::PUBLISHED);
      $query->sort('changed', 'DESC');

      if (!empty($tags)) {
        // Add conditions to limit to specific tags, either on the document
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
      $entity_ids = $query->execute();

      $entities = $entity_ids ? $this->entityTypeManager
        ->getStorage($type)
        ->loadMultiple($entity_ids) : [];

      if (!$entities) {
        // If there is no entity with this id, add the list cache tags so that
        // the cache entry is purged whenever a new entity of this type is
        // saved.
        $type = $this->entityTypeManager->getDefinition($type);
        /** @var \Drupal\Core\Entity\EntityTypeInterface $type */
        $tags = $type->getListCacheTags();
        $context->addCacheTags($tags);
        return [];
      }

      foreach ($entities as $id => $entity) {
        $context->addCacheableDependency($entities[$id]);
        if (isset($bundles) && !in_array($entities[$id]->bundle(), $bundles)) {
          // If the entity is not among the allowed bundles, don't return it.
          unset($entities[$id]);
          continue;
        }

        if (isset($language) && $language !== $entities[$id]->language()->getId() && $entities[$id] instanceof TranslatableInterface) {
          $entities[$id] = $entities[$id]->getTranslation($language);
          $entities[$id]->addCacheContexts(["static:language:{$language}"]);
        }

        /** @var \Drupal\Core\Access\AccessResultInterface $accessResult */
        $accessResult = $entities[$id]->access('view', NULL, TRUE);
        $context->addCacheableDependency($accessResult);
        // We need to call isAllowed() because isForbidden() returns FALSE
        // for neutral access results, which is dangerous. Only an explicitly
        // allowed result means that the user has access.
        if (!$accessResult->isAllowed()) {
          unset($entities[$id]);
          continue;
        }

        // Filter out documents that are not tagged yet.
        if ($entities[$id]->get('field_tags')->isEmpty()) {
          unset($entities[$id]);
          continue;
        }

        // Filter out documents that are not associated to a content space.
        if ($entities[$id]->get('field_content_space')->isEmpty()) {
          unset($entities[$id]);
          continue;
        }

        // @todo Filter out documents that are associated to a content space
        // which has no tags. See if this is necessary.
      }

      return (object) [
        'count' => count($entities),
        'items' => $entities,
      ];
    });
  }

}
