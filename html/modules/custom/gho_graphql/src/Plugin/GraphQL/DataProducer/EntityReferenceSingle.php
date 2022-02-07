<?php

namespace Drupal\gho_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql\Plugin\GraphQL\DataProducer\Field\EntityReferenceTrait;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads entities from an entity reference field.
 *
 * @DataProducer(
 *   id = "entity_reference_single",
 *   name = @Translation("Entity reference single"),
 *   description = @Translation("Loads a single entity from an entity reference field."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Parent entity")
 *     ),
 *     "field" = @ContextDefinition("string",
 *       label = @Translation("Field name")
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Entity language"),
 *       required = FALSE
 *     ),
 *     "bundle" = @ContextDefinition("string",
 *       label = @Translation("Entity bundle(s)"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *     "delta" = @ContextDefinition("integer",
 *       label = @Translation("Delta"),
 *       required = FALSE,
 *       default_value = 0
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
class EntityReferenceSingle extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use EntityReferenceTrait;

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
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
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
      $container->get('graphql.buffer.entity')
    );
  }

  /**
   * Constructor.
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
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
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
    EntityBuffer $entityBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->entityBuffer = $entityBuffer;
  }

  /**
   * Retrieves a single referenced entity from the given resolver.
   *
   * May optionally respect bundles/language and perform access checks.
   *
   * @param string $type
   *   Entity type ID.
   * @param string|null $language
   *   Optional. Language to be respected for retrieved entities.
   * @param array|null $bundles
   *   Optional. List of bundles to be respected for retrieved entities.
   * @param int|null $delta
   *   Optional. The delta to fetch.
   * @param bool|null $access
   *   Whether to filter out inaccessible entities.
   * @param \Drupal\Core\Session\AccountInterface|null $accessUser
   *   User entity to check access for. Default is null.
   * @param string $accessOperation
   *   Operation to check access for. Default is view.
   * @param \Closure $resolver
   *   The resolver to execute.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The caching context related to the current field.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The referenced entity. Or NULL.
   */
  protected function getReferencedEntity(string $type, ?string $language, ?array $bundles, ?int $delta, ?bool $access, ?AccountInterface $accessUser, string $accessOperation, \Closure $resolver, FieldContext $context): ?EntityInterface {

    $entities = $this->getReferencedEntities($type, $language, $bundles, $access, $accessUser, $accessOperation, $resolver, $context);
    $entities = $entities ? array_values($entities) : [];
    $delta = $delta ?? 0;
    return array_key_exists($delta, $entities) ? $entities[$delta] : NULL;
  }

  /**
   * Resolve entity references in the given field name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $field
   *   The field that holds the reference.
   * @param string|null $language
   *   Optional. Language to be respected for retrieved entities.
   * @param array|null $bundles
   *   Optional. List of bundles to be respected for retrieved entities.
   * @param int|null $delta
   *   Optional. The delta to fetch.
   * @param bool|null $access
   *   Whether to filter out inaccessible entities.
   * @param \Drupal\Core\Session\AccountInterface|null $accessUser
   *   User entity to check access for. Default is null.
   * @param string|null $accessOperation
   *   Operation to check access for. Default is view.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The caching context related to the current field.
   *
   * @return \GraphQL\Deferred|null
   *   A promise object.
   */
  public function resolve(EntityInterface $entity, $field, ?string $language, ?array $bundles, ?int $delta, ?bool $access, ?AccountInterface $accessUser, ?string $accessOperation, FieldContext $context) {
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($field)) {
      return NULL;
    }

    $definition = $entity->getFieldDefinition($field);
    $type = $definition->getSetting('target_type');
    $values = $entity->get($field);
    if ($values instanceof EntityReferenceFieldItemListInterface) {
      $ids = array_map(function ($value) {
        return $value['target_id'];
      }, $values->getValue());

      $resolver = $this->entityBuffer->add($type, $ids);
      return new Deferred(function () use ($type, $language, $bundles, $delta, $access, $accessUser, $accessOperation, $resolver, $context) {
        return $this->getReferencedEntity($type, $language, $bundles, $delta, $access, $accessUser, $accessOperation, $resolver, $context);
      });
    }

    return NULL;
  }

}
