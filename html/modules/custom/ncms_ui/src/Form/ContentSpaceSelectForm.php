<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\ncms_ui\ContentSpaceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ContentSpaceSelectForm to select the current content space.
 */
class ContentSpaceSelectForm extends FormBase {

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * Class constructor.
   *
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The ncms content manager service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   *   The render cache service.
   */
  public function __construct(ContentSpaceManager $content_manager, CurrentPathStack $current_path, CacheBackendInterface $render_cache) {
    $this->contentSpaceManager = $content_manager;
    $this->currentPath = $current_path;
    $this->renderCache = $render_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ncms_ui.content.manager'),
      $container->get('path.current'),
      $container->get('cache.render'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ncms_ui_content_space_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_spaces = $this->contentSpaceManager->getContentSpaces();
    $content_space_ids_user = $this->contentSpaceManager->getValidContentSpaceIdsForCurrentUser();

    $options_user = [];
    $options_global = [];
    foreach ($content_spaces as $term) {
      if (in_array($term->id(), $content_space_ids_user)) {
        $options_user[$term->id()] = $term->label();
      }
      else {
        $options_global[$term->id()] = $term->label();
      }
    }
    if ($form_state->hasValue('content_space')) {
      // If submitted, update the currently selected content space.
      $this->contentSpaceManager->setCurrentContentSpace($form_state->getValue('content_space'));
      $this->renderCache->invalidateAll();
    }
    $input = $form_state->getUserInput();
    $form['current_path'] = [
      '#type' => 'hidden',
      '#value' => $input['current_path'] ?? $this->currentPath->getPath(),
    ];

    // Build the content space drop down.
    $form['content_space'] = [
      '#type' => 'select',
      '#title' => $this->t('Content space'),
      '#options' => [
        (string) $this->t('My content spaces') => $options_user,
        (string) $this->t('Other content spaces') => $options_global,
      ],
      '#default_value' => $this->contentSpaceManager->getCurrentContentSpace(),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'abc',
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback that just reloads the current page.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajaxCallback() {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand('/'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
