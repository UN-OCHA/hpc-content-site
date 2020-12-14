<?php

namespace Drupal\gho_fields\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present links to edit, view or create an entity translation.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("gho_translation_links")
 */
class GhoTranslationLinks extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    // This doesn't make sense if the site is not multilingual.
    if (!$this->languageManager->isMultilingual()) {
      return [];
    }

    $entity = $this->getEntity($row);
    $language = $this->languageManager->getLanguage($this->options['langcode']);

    // Supported entity types.
    $entity_types = ['media', 'node', 'taxonomy_term'];
    if (!$entity || !in_array($entity->getEntityTypeId(), $entity_types)) {
      return [];
    }

    $build = [
      '#type' => 'container',
    ];

    if ($entity->hasTranslation($language->getId())) {
      $entity = $entity->getTranslation($language->getId());
      $status = $entity->isPublished() ? ' ✔' : ' ✖';

      // Only nodes have individual pages so only add the view link for them.
      if ($entity->getEntityTypeId() === 'node') {
        $build['edit'] = $this->getLink($this->t('edit'), $entity->toUrl('edit-form'));
        $build['view'] = $this->getLink($this->t('view'), $entity->toUrl('canonical'), $status);
      }
      else {
        $build['edit'] = $this->getLink($this->t('edit'), $entity->toUrl('edit-form'), $status);
      }
    }
    else {
      $build['create'] = $this->getLink($this->t('create'), $this->getTranslationCreateUrl($entity));
    }

    return $build;
  }

  /**
   * Generate a render array for a link.
   *
   * @param string $title
   *   Link title.
   * @param \Drupal\Core\Url|null $url
   *   Link URL.
   * @param string $suffix
   *   Link suffix (this used to show the published status).
   *
   * @return array
   *   Link render array.
   */
  protected function getLink($title, ?Url $url, $suffix = '') {
    if (empty($url)) {
      return [];
    }

    $language = $this->languageManager->getLanguage($this->options['langcode']);
    $url->setOption('language', $language);

    if ($url->access()) {
      return [
        '#type' => 'link',
        '#title' => $title,
        '#url' => $url,
        '#prefix' => '<div>',
        '#suffix' => $suffix . '</div>',
      ];
    }

    return [];
  }

  /**
   * Get the URL to create a translation of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Url
   *   Entity creation URL or NULL.
   */
  protected function getTranslationCreateUrl(EntityInterface $entity) {
    $id = $entity->id();
    $langcode = $this->options['langcode'];
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    switch ($entity->getEntityTypeId()) {
      case 'media':
        $uri = "/media/$id/edit/translations/add/$default_langcode/$langcode";
        break;

      case 'node':
        $uri = "/node/$id/translations/add/$default_langcode/$langcode";
        break;

      case 'taxonomy_term':
        $uri = "/taxonomy/term/$id/translations/add/$default_langcode/$langcode";
        break;
    }

    return !empty($uri) ? Url::fromUri('internal:' . $uri) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $language = $this->languageManager->getLanguage($this->options['langcode']);
    return $language->getName();
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    // This is not used but we need to implement it.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $language = $this->languageManager->getDefaultLanguage();
    $options = parent::defineOptions();
    $options['label'] = ['default' => $language->getName()];
    $options['langcode'] = ['default' => $language->getId()];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Link labels are defined in ::render().
    $form['text']['#access'] = FALSE;

    // Label is set to the selected language.
    $form['label']['#access'] = FALSE;

    // Add a selector for the link language.
    $options = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $form['langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $options,
      '#default_value' => $this->options['langcode'],
      '#description' => $this->t('Select the language for the translation'),
    ];
  }

}
