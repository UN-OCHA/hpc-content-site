<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ncms_ui\Entity\Media\MediaBase;
use Drupal\ncms_ui\Plugin\views\ContentBaseField;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Provides a field that shows the count of places an entity is used in.
 */
#[ViewsField("places_used_count_field")]
class PlacesUsedCountField extends ContentBaseField {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_usage_count_page'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_usage_count_page'] = [
      '#title' => $this->t('Link to usage count page'),
      '#description' => $this->t('Make the number a link to the usage count page for the entity.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_usage_count_page']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity = $row->_entity;
    if (!$entity instanceof MediaBase) {
      return;
    }
    $references = $entity->getUsageReferences();
    $count = count($references['optional']) + count($references['mandatory']);
    $places_used_url = NULL;
    if (!empty($this->options['link_to_usage_count_page'])) {
      try {
        $places_used_url = $entity->toUrl('places-used');
      }
      catch (UndefinedLinkTemplateException $e) {
        // Just fail silently.
      }
    }
    if ($places_used_url) {
      $build = [
        '#type' => 'link',
        '#title' => $count,
        '#url' => $places_used_url,
      ];
    }
    else {
      $build = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $count,
      ];
    }
    return $build;
  }

}
