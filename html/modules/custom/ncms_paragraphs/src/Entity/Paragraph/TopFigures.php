<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItemList;
use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;

/**
 * Entity class for paragraphs of type top figures.
 */
class TopFigures extends NcmsParagraphBase {

  const EMPHASIS_OPTION_NORMAL = 'normal';
  const EMPHASIS_OPTION_NONE = 'none';
  const EMPHASIS_OPTION_HIGHLIGHT = 'highlight';
  const USE_EMPHASIS = TRUE;

  /**
   * Check if this paragraph should support emphasis of values.
   *
   * @return bool
   *   TRUE if the paragraph should support emphasis, FALSE otherwise.
   */
  public function useEmphasis() {
    return self::USE_EMPHASIS;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state) {
    parent::entityFormAlter($form, $form_state);

    /** @var \Drupal\ncms_paragraphs\Entity\NcmsParagraphGeneric $paragraph */
    $paragraph = $form['#paragraph'];
    foreach ($paragraph->getFields() as $field_name => $field) {
      if (!$field instanceof CustomItemList) {
        continue;
      }
      foreach (Element::children($form[$field_name]['widget']) as $element_key) {
        if (!is_int($element_key)) {
          continue;
        }
        $widget = &$form[$field_name]['widget'][$element_key];
        if (!is_array($widget) || empty($widget['emphasis'])) {
          continue;
        }
        if ($this->useEmphasis()) {
          $select = [
            '#type' => 'select',
            '#options' => [
              self::EMPHASIS_OPTION_NORMAL => $this->t('Normal (Blue)'),
              self::EMPHASIS_OPTION_NONE => $this->t('De-emphasized (Grey)'),
              self::EMPHASIS_OPTION_HIGHLIGHT => $this->t('Highlighted (Red)'),
            ],
            '#size' => 0,
          ];
          $widget['emphasis'] = $select + $widget['emphasis'];
        }
        else {
          $widget['emphasis']['#type'] = 'value';
          $widget['emphasis']['#value'] = NULL;
        }
      }
    }
  }

}
