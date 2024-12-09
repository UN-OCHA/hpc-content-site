<?php

namespace Drupal\ncms_ui\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser_table\Plugin\Field\FieldWidget\EntityReferenceBrowserTableWidget;

/**
 * Plugin implementation of the 'entity_reference_browser_table_widget' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_browser_table_widget_ncms",
 *   label = @Translation("Entity Browser - Table (NCMS)"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceBrowserTableWidgetNcms extends EntityReferenceBrowserTableWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['current']['#weight'] = -1;
    return $element;
  }

  /**
   * Builds the render array for displaying the current results as a table.
   *
   * @param string $details_id
   *   The ID for the details element.
   * @param string[] $field_parents
   *   Field parents.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of referenced entities.
   *
   * @return array
   *   The render array for the current selection.
   */
  protected function displayCurrentSelection($details_id, array $field_parents, array $entities) {
    $entity_browser = $this->getEntityBrowser();
    $add_more_button_label = $entity_browser->getDisplay()->getConfiguration()['link_text'];
    try {
      $header = $this->buildTableHeaders();
      if (!$this->isSortable()) {
        unset($header[0]);
      }
      $table = [
        '#type' => 'table',
        '#header' => $header,
        '#attributes' => [
          'class' => array_filter([
            'table--widget-entity_reference_browser_table_widget',
            $this->isSortable() ? 'table--widget-entity_reference_browser_table_widget--sortable' : NULL,
          ]),
        ],
        '#empty' => $this->t('No articles added yet. Use the <em>@button_label</em> button below to add articles.', [
          '@button_label' => $add_more_button_label,
        ]),
      ];
      return array_merge($table, $this->buildTableRows($entities, $details_id, $field_parents));
    }
    catch (PluginException $exception) {
      \Drupal::logger('Entity Browser - Table Display')
        ->error($this->t(
          'Could not get the field widget display: @message',
          ['@message' => $exception->getMessage()]
        ));

      return $table = [
        '#type' => 'table',
        '#header' => [''],
        '#rows' => [
          [
            $this->t('The field widget could not be found. See the logs for details'),
          ],
        ],
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildTableRows(array $entities, $details_id, $field_parents) {
    /** @var \Drupal\entity_browser\FieldWidgetDisplayInterface $field_widget_display */
    $field_widget_display = $this->getFieldWidgetDisplay();

    $entities = array_filter($entities, function ($entity) {
      return $entity instanceof EntityInterface;
    });
    $rowData = [];
    foreach ($entities as $row_id => $entity) {
      if ($entity->hasTranslation($this->currentLanguage) == TRUE) {
        $entity = $entity->getTranslation($this->currentLanguage);
      }

      $rowData[] = array_filter([
        'handle' => $this->isSortable() ? $this->buildSortableHandle() : NULL,
        'title-preview' => $this->getFirstColumn($entity),
        'status' => $this->getAdditionalFieldsColumn($entity),
        'actions' => [
          'replace_button' => $this->buildReplaceButton($entity, $entities, $details_id, $row_id, $field_parents),
          'remove_button' => $this->buildRemoveButton($entity, $details_id, $row_id, $field_parents),
        ],
        '#attributes' => [
          'class' => [
            'item-container',
            Html::getClass($field_widget_display->getPluginId()),
          ],
          'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
          'data-row-id' => $row_id,
        ],

      ]);
    }

    return $rowData;
  }

  /**
   * Check if the table should be sortable.
   *
   * @return bool
   *   TRUE if the table should be sortable, FALSE otherwise.
   */
  private function isSortable() {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    return $cardinality > 1 || $cardinality == -1;
  }

  /**
   * Get the entity browser used with this widget.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The entity browser object.
   */
  private function getEntityBrowser() {
    return $this->entityTypeManager->getStorage('entity_browser')->load($this->getSetting('entity_browser'));
  }

}
