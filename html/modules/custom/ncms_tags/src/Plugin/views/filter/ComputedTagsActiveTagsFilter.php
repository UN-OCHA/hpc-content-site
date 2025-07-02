<?php

namespace Drupal\ncms_tags\Plugin\views\filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tag filtering on computed tags field, using active tags widgets.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("computed_tags_active_tags")]
class ComputedTagsActiveTagsFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   */
  public $no_operator = TRUE;
  // @codingStandardsIgnoreEnd

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The common taxonomies service.
   *
   * @var \Drupal\ncms_tags\CommonTaxonomyService
   */
  protected $commonTaxonomies;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->commonTaxonomies = $container->get('ncms_tags.common_taxonomies');
    return $instance;
  }

  /**
   * Options form subform for setting options.
   *
   * This should be overridden by all child classes and it must
   * define $form['value']
   *
   * @see buildOptionsForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Only present a checkbox for the exposed filter itself.
    if ($form_state->get('exposed')) {
      $form['value'] = [
        '#type' => 'entity_autocomplete_active_tags',
        '#target_type' => 'taxonomy_term',
        '#tags' => TRUE,
        '#default_value' => NULL,
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'tag_filter',
            'display_name' => 'autocomplete_source',
          ],
          'min_length' => 1,
        ],
        '#match_limit' => 10,
        '#min_length' => 1,
        '#delimiter' => '',
        '#style' => 'rectangle',
        '#show_entity_id' => FALSE,
        '#element_validate' => [[$this, 'elementValidate']],
        '#after_build' => [[self::class, 'afterBuild']],
        '#attributes' => ['class' => [Html::getClass('computed_tags_filter')]],
        '#attached' => ['library' => ['ncms_tags/input.computed_tags']],
      ];
    }
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    $tids = array_filter(array_map(function ($item) {
      return $item['target_id'];
    }, $this->value));
    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($tids);

    $bundle_field_map = $this->commonTaxonomies->getCommonTaxonomyBundleFieldMap();
    foreach ($terms as $term) {
      if (empty($bundle_field_map[$term->bundle()])) {
        continue;
      }
      $table_name = 'node__' . $bundle_field_map[$term->bundle()];
      $field_name = $bundle_field_map[$term->bundle()] . '_target_id';
      $table_alias = $this->query->addTable($table_name);
      if (!$table_alias) {
        continue;
      }
      $this->query->addWhere($this->options['group'], "$table_alias.$field_name", $term->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
  }

  /**
   * Validates and processes the autocomplete element values.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \JsonException
   */
  public static function elementValidate(array $element, FormStateInterface $form_state): void {
    $value = $form_state->getValue($element['#parents']);
    if ($value && ($items = Json::decode($value))) {
      $formatted_items = self::formattedItems($items);
      if (!empty($formatted_items)) {
        $form_state->setValue($element['#parents'], $formatted_items);
      }
    }
  }

  /**
   * Formats filter items.
   *
   * @param array $items
   *   The filter items.
   *
   * @return array
   *   The formatted filter items.
   */
  protected static function formattedItems(array $items): array {
    foreach ($items as $item) {
      $formatted_items[] = [
        'target_id' => $item['entity_id'],
        'label' => $item['label'],
      ];
    }

    return $formatted_items ?? [];
  }

  /**
   * Form API after build callback for the autocomplete input element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    // By default, Drupal sets the maxlength to 128 if the property isn't
    // specified, but since the limit isn't useful in some cases,
    // we unset the property.
    unset($element['#maxlength']);
    return $element;
  }

}
