<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for an alert form if no tags have been added yet.
 */
class ContentSubmitNoTagsAlertForm extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_submit_no_tags_alert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $bundle = $this->getRouteMatch()->getRawParameter('node_type') ?? $this->getRouteMatch()->getParameter('node')->bundle();
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle) ?? $this->t('entity');
    $node_type_label = strtolower($node_type->label());
    if (in_array($node_type_label[0], ['a', 'e', 'i', 'o', 'u'])) {
      return $this->t('An @type cannot be published without any tags associated with it. Please add at least one tag and try again, or save the @type as a draft instead', [
        '@type' => $node_type_label,
      ]);
    }
    else {
      return $this->t('A @type cannot be published without any tags associated with it. Please add at least one tag and try again, or save the @type as a draft instead', [
        '@type' => $node_type_label,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Ok');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $triggering_element = NULL, $entity = NULL) {
    $form = parent::buildForm($form, $form_state);

    $form['description'] = ['#markup' => $this->getQuestion()];
    $form['actions']['submit']['#access'] = FALSE;
    // This is a special class to which JavaScript assigns dialog closing
    // behavior.
    $form['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';
    $form['actions']['cancel']['#title'] = $this->getConfirmText();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty stub just because FormInterface requires it.
  }

}
