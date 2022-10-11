<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns entity specific configuration.
 *
 * @DataProducer(
 *   id = "entity_configuration",
 *   name = @Translation("Entity configuration"),
 *   description = @Translation("Returns the configuration of the entity. This only applies to paragraph entities"),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Stringified entity configurarion")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     )
 *   }
 * )
 */
class EntityConfiguration extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * EntityRendered constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * Resolver.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cachability metadata.
   *
   * @return string
   *   The configuration as a yaml encoded string.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata) {
    $context = new RenderContext();
    $result = $this->renderer->executeInRenderContext($context, function () use ($entity) {
      $config = [];
      if ($entity instanceof Paragraph) {
        if ($entity->getType() == 'article_list') {
          $config['links'] = [];
          foreach ($entity->get('field_links') as $item) {
            /** @var \Drupal\core\Url $url */
            $url = $item->getUrl();
            $config['links'][] = $item->getValue() + [
              'route_name' => $url->getRouteName(),
              'route_parameters' => $url->getRouteParameters(),
              'alias' => $url->toString(),
            ];
          }
        }
      }
      return Yaml::encode($this->mapObjectsToString($config));
    });

    if (!$context->isEmpty()) {
      $metadata->addCacheableDependency($context->pop());
    }
    return (string) $result;
  }

  /**
   * Map an array of items to strings.
   *
   * This turns objects into strings to the extent possible.
   *
   * @param array $array
   *   The array to process.
   *
   * @return array
   *   The processed array.
   */
  public function mapObjectsToString(array $array) {
    foreach ($array as $key => $value) {
      if (is_object($value)) {
        $array[$key] = (string) $value;
      }
      if (is_array($value)) {
        $array[$key] = self::mapObjectsToString($value);
      }
    }
    return $array;
  }

}
