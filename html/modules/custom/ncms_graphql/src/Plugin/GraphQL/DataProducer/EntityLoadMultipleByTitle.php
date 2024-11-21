<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\ncms_graphql\Wrappers\ContentSearchWrapper;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load multiple entities by title.
 *
 * @DataProducer(
 *   id = "entity_search_by_title",
 *   name = @Translation("Load multiple entities by title"),
 *   description = @Translation("Loads multiple entities by title."),
 *   produces = @ContextDefinition("entities",
 *     label = @Translation("Entities")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Entity type")
 *     ),
 *     "title" = @ContextDefinition("string",
 *       label = @Translation("Title"),
 *       multiple = FALSE
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Entity language"),
 *       required = FALSE
 *     ),
 *     "bundles" = @ContextDefinition("string",
 *       label = @Translation("Entity bundle(s)"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *     "access" = @ContextDefinition("boolean",
 *       label = @Translation("Check access"),
 *       required = FALSE,
 *       default_value = TRUE
 *     ),
 *     "access_user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = FALSE,
 *       default_value = NULL
 *     ),
 *     "access_operation" = @ContextDefinition("string",
 *       label = @Translation("Operation"),
 *       required = FALSE,
 *       default_value = "view"
 *     )
 *   }
 * )
 */
class EntityLoadMultipleByTitle extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity buffer service.
   *
   * @var \Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer
   */
  protected $entityBuffer;

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
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('ncms_graphql.buffer.entity')
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
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer $entityBuffer
   *   The entity buffer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    EntityMatchingBuffer $entityBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->entityBuffer = $entityBuffer;
  }

  /**
   * Resolver.
   *
   * @param string $type
   *   The entity type to search in.
   * @param string $title
   *   The title to search for.
   * @param string|null $language
   *   An optional language string.
   * @param array|null $bundles
   *   The entity bundles to search in.
   * @param bool $access
   *   Whether to do access checks.
   * @param \Drupal\Core\Session\AccountInterface|null $accessUser
   *   The current user.
   * @param string|null $accessOperation
   *   The access operation.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   A context object.
   *
   * @return \GraphQL\Deferred
   *   A promise.
   */
  public function resolve($type, string $title, ?string $language, ?array $bundles, bool $access, ?AccountInterface $accessUser, ?string $accessOperation, FieldContext $context) {

    $resolver = $this->entityBuffer->addTitleString($type, $title, $bundles);

    return new Deferred(function () use ($type, $language, $resolver, $context, $accessUser, $accessOperation) {
      /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
      $entities = $resolver();

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

        if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
          unset($entities[$id]);
          continue;
        }

        if (isset($language) && $language !== $entities[$id]->language()->getId() && $entities[$id] instanceof TranslatableInterface) {
          $entities[$id] = $entities[$id]->getTranslation($language);
          $entities[$id]->addCacheContexts(["static:language:{$language}"]);
        }

        /** @var \Drupal\Core\Access\AccessResultInterface $accessResult */
        $accessResult = $entities[$id]->access($accessOperation, $accessUser, TRUE);
        $context->addCacheableDependency($accessResult);
        // We need to call isAllowed() because isForbidden() returns FALSE
        // for neutral access results, which is dangerous. Only an explicitly
        // allowed result means that the user has access.
        if (!$accessResult->isAllowed()) {
          unset($entities[$id]);
          continue;
        }
      }
      return new ContentSearchWrapper($entities);
    });
  }

}
