<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that shows the the status of a node based on its versions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_status_field")
 */
class ContentStatusField extends FieldPluginBase {

  /**
   * The entity typemanager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Overridden to prevent any additional query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    if (!$row->_entity instanceof ContentInterface) {
      return NULL;
    }
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $row->_entity->getContentStatusLabel(),
      '#attributes' => [
        'class' => array_filter([
          'marker',
          $row->_entity->isPublished() ? 'marker--published' : NULL,
        ]),
      ],
    ];
    return $build;
  }

}
