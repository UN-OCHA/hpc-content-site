<?php

namespace Drupal\ncms_publisher\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a paragraphs behaviour for promoting paragraphs.
 *
 * @ParagraphsBehavior(
 *   id = "promoted_behavior",
 *   label = @Translation("Promotable paragraph"),
 *   description = @Translation("Add option to mark a paragraph as promoted"),
 * )
 */
class PromoteBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $promoted = $paragraph->getBehaviorSetting($this->getPluginId(), 'promoted', FALSE);
    if ($promoted) {
      $build['#attributes']['class'][] = 'gho-paragraph-promoted';
      $build['#attached']['library'][] = 'common_design_subtheme/gho-promoted-paragraph';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['promoted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promoted'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'promoted', FALSE),
    ];
    return $form;
  }

}
