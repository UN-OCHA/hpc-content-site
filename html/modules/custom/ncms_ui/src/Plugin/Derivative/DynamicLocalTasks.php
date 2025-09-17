<?php

namespace Drupal\ncms_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Plugin\Menu\LocalTask\MediaUsageLocalTask;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates local tasks for the trash bin.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * Public constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router.
   */
  public function __construct($base_plugin_id, RouterInterface $router) {
    $this->basePluginId = $base_plugin_id;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('router')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Add the trash tasks if the views routes exists.
    if ($this->router->getRouteCollection()->get('view.trash_nodes.page_trash')) {
      $parent_id = $this->basePluginId . ':trash';
      $this->derivatives['trash'] = [
        'title' => (string) $this->t('Trash'),
        'route_name' => 'view.trash_nodes.page_trash',
        'base_route' => 'system.admin_content',
        'weight' => 99,
      ];

      $this->derivatives['trash_content'] = [
        'title' => (string) $this->t('Content'),
        'parent_id' => $parent_id,
        'route_name' => 'view.trash_nodes.page_trash',
        'weight' => 10,
      ];

      if ($this->router->getRouteCollection()->get('view.trash_media.page_trash')) {
        $this->derivatives['trash_media'] = [
          'title' => (string) $this->t('Media'),
          'parent_id' => $parent_id,
          'route_name' => 'view.trash_media.page_trash',
          'weight' => 20,
        ];
      }
    }
    // Add the places used tasks if the views routes exists.
    if ($this->router->getRouteCollection()->get('view.media_usage.page_content')) {
      $parent_id = $this->basePluginId . ':media_usage';
      $this->derivatives['media_usage'] = [
        'title' => (string) $this->t('Places used'),
        'route_name' => 'view.media_usage.page_content',
        'base_route' => 'entity.media.canonical',
        'weight' => 99,
        'class' => MediaUsageLocalTask::class,
      ];

      $this->derivatives['media_usage_content'] = [
        'title' => (string) $this->t('Content'),
        'parent_id' => $parent_id,
        'route_name' => 'view.media_usage.page_content',
        'weight' => 10,
        'class' => MediaUsageLocalTask::class,
      ];

      if ($this->router->getRouteCollection()->get('view.media_usage.page_paragraphs')) {
        $this->derivatives['media_usage_paragraphs'] = [
          'title' => (string) $this->t('Paragraphs'),
          'parent_id' => $parent_id,
          'route_name' => 'view.media_usage.page_paragraphs',
          'weight' => 20,
          'class' => MediaUsageLocalTask::class,
        ];
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
