<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\ncms_ui\Traits\ModalLinkTrait;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to soft-delete an entity.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("entity_link_soft_delete")]
class EntityLinkSoftDelete extends EntityLink {

  use ModalLinkTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'soft-delete-form';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $template = $this->getEntityLinkTemplate();
    $entity = $this->getEntity($row);
    if ($entity === NULL) {
      return NULL;
    }
    if ($this->languageManager->isMultilingual()) {
      $entity = $this->getEntityTranslationByRelationship($entity, $row);
    }
    $url_options = $this->getModalUrlOptions($this->t('Confirm delete'));
    return $entity->toUrl($template, $url_options)->setAbsolute($this->options['absolute']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Move to trash');
  }

}
