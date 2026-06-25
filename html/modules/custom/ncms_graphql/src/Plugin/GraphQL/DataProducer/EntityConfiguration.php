<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer = $container->get('renderer');
    return $instance;
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
            /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item */
            /** @var \Drupal\core\Url $url */
            $url = $item->getUrl();
            if (!$url->isRouted()) {
              // If there is no route, there is nothing that we can export.
              continue;
            }
            $config['links'][] = $item->getValue() + [
              'route_name' => $url->getRouteName(),
              'route_parameters' => $url->getRouteParameters(),
              'alias' => $url->toString(),
            ];
          }
        }
        if ($original_uuid = $entity->getBehaviorSetting('ncms_paragraphs', 'replaces')) {
          $config['replaces'] = $original_uuid;
        }
        if ($entity->getType() == 'sub_article') {
          $config['article_id'] = $entity->get('field_article')->target_id;
          $config['collapsible'] = (bool) $entity->get('field_collapsible')->value;
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
