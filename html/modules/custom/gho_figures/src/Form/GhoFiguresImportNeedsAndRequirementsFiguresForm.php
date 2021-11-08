<?php

namespace Drupal\gho_figures\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\Entity\Term;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GHO figures import form implementation.
 */
class GhoFiguresImportNeedsAndRequirementsFiguresForm extends FormBase {

  /**
   * Supported spreadsheet columns.
   *
   * Each column defintion can contain the following values:
   * - legacy: indicates that the column itself is not used as is anymore
   *   though it may still be used when processing other columns.
   * - mandatory: indicates that the column must be present.
   * - multiple: indicates that there may be multiple values for the column.
   * - field: indicates the "needs and requirements" term's field to which the
   *   column data will be added.
   * - preprocess: array with a callback as first item and additional
   *   arguments. The cell data will be preprocessed by this callback when
   *   parsing a row. The callback will be passed the whole row's data by
   *   reference and is expecting to modify the data (no return value).
   *   The callback should return FALSE if the value was invalid.
   * - process: array with a callback as first item and additional
   *   arguments. The cell data will be processed by this callback when
   *   added to the field.
   * - table_display: array with a callback as first item and additional
   *   arguments. The function will be called to generate the value to
   *   display in the figures table in the confirmation step. The callback will
   *   be passed the whole row's data.
   *
   * @var array
   */
  public static $columns = [
    'name' => [
      'mandatory' => TRUE,
      'field' => 'name',
      'label' => '',
    ],
    'people in need' => [
      'mandatory' => TRUE,
      'field' => 'field_people_in_need',
      'label' => 'People in need',
      'preprocess' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::preprocessNumber',
      ],
      'table_display' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::displayNumber',
      ],
    ],
    'people targeted' => [
      'mandatory' => TRUE,
      'field' => 'field_people_targeted',
      'label' => 'People targeted',
      'preprocess' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::preprocessNumber',
      ],
      'table_display' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::displayNumber',
      ],
    ],
    'requirements (us$)' => [
      'mandatory' => TRUE,
      'field' => 'field_requirements',
      'label' => 'Requirements (US$)',
      'preprocess' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::preprocessNumber',
      ],
      'table_display' => [
        '\Drupal\gho_figures\Form\GhoFiguresImportNeedsAndRequirementsFiguresForm::displayNumber',
      ],
    ],
  ];

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(Connection $database, FileSystemInterface $file_system, ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->database = $database;
    $this->fileSystem = $file_system;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('file_system'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * Get a setting for the gho_figures module.
   *
   * @param string $setting
   *   Setting name.
   * @param mixed $default
   *   Default value for the setting.
   *
   * @return mixed
   *   Value for the setting.
   */
  public function getSetting($setting, $default = NULL) {
    static $settings;
    if (!isset($settings)) {
      $settings = $this->configFactory->get('gho_figures.settings');
    }
    return $settings->get($setting) ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gho_figures_import_needs_and_requirements_figures_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = [
      '#type' => 'actions',
      // Ensure it's at the bottom of the list.
      '#weight' => 10,
    ];

    if ($form_state->has('step') && $form_state->get('step') == 2) {
      return self::buildFormStepTwo($form, $form_state);
    }
    else {
      return self::buildFormStepOne($form, $form_state);
    }
  }

  /**
   * Build the first step of the form with the file upload.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Modified form.
   */
  public function buildFormStepOne(array $form, FormStateInterface $form_state) {
    $form_state->set('step', 1);

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('<h2>Step 1: File Upload</h2>'),
    ];

    $form['tips'] = [
      '#type' => 'item',
      '#markup' => $this->t('
        <h3>Tips</h3>
        <ul>
          <li>Make sure the spreadsheet contains those 4 columns:
            <ol>
              <li>"Name" column (with overviews and appeals; assumed to be the first column)</li>
              <li>People in need</li>
              <li>People targeted</li>
              <li>Requirements (US$)</li>
            </ol>
          </li>
          <li>Make sure all the figures are non formatted numbers (ex: 1200000 not 1.2 million)</li>
          <li>Use 0, -1, - or TBC to indicate a missing value</li>
        </ul>
      '),
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Spreadsheet file'),
      '#description' => $this->t('Spreadsheet file with the figures. Accepted formats are: xls, xlsx, ods, csv.'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('file'),
      '#upload_location' => 'temporary://figures-import',
      '#upload_validators' => [
        'file_validate_extensions' => ['xls xlsx ods csv'],
      ],
    ];

    // Proceed to step 2.
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::submitFormStepOne'],
      '#validate' => ['::validateFormStepOne'],
    ];

    return $form;
  }

  /**
   * Validate the first step of the form.
   *
   * Here we also parse the spreadsheet file and extract the figures.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateFormStepOne(array &$form, FormStateInterface $form_state) {
    $fids = $form_state->getValue('file');
    if (!empty($fids)) {
      // Load the file object and get its real path as that's what
      // PHPSpreadsheet expects.
      $file = $this->entityTypeManager->getStorage('file')->load(reset($fids));
      $path = $this->fileSystem->realpath($file->getFileUri());

      // Maximum number of rows that can be parsed to limit memory consumption.
      // In our case, there will be probably be less than 100 rows.
      $max_rows = $this->getSetting('max_rows', 9999);

      // Extract the figures from the spreadsheet.
      $figures = static::parseSpreadsheet($path, $max_rows);
      if (empty($figures['figures']) && !empty($figures['errors'])) {
        $form_state->setErrorByName('file', $this->t("Unable to extract figures from the spreadsheet: \n@errors.", [
          '@errors' => static::formatList($figures['errors']),
        ]));
      }
      else {
        $form_state->set('figures', $figures);
      }
    }
  }

  /**
   * Submit the first step of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitFormStepOne(array &$form, FormStateInterface $form_state) {
    $form_state
      ->set('first_step_values', ['file' => $form_state->getValue('file')])
      ->set('step', 2)
      ->setRebuild(TRUE);
  }

  /**
   * Build the second step of the form with the confirmation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Modified form.
   */
  public function buildFormStepTwo(array $form, FormStateInterface $form_state) {
    $form_state->set('step', 2);

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('<h2>Step 2: Confirmation</h2>'),
    ];

    // Display the table with the list of figures to import.
    $figures = $form_state->get('figures');
    if (!empty($figures['figures'])) {
      // Maximum number of figures to show.
      $count = count($figures['figures']);
      $headers = static::getTableHeaders($figures['columns']);
      $rows = static::getTableRows($headers, $figures['figures']);

      $form['figure-list'] = [
        '#type' => 'details',
        '#title' => $this->formatPlural($count,
          '@count figure to import',
          '@count figures to import'
        ),
        'table' => [
          '#type' => 'table',
          '#header' => $headers,
          '#rows' => $rows,
        ],
      ];
    }

    // Display the errors detected while parsing the spreadsheet.
    if (!empty($figures['errors'])) {
      $count = count($figures['errors']);

      $form['error-list'] = [
        '#type' => 'details',
        '#title' => $this->formatPlural($count,
          '@count parsing error',
          '@count parsing errors'
        ),
        'list' => [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => $figures['errors'],
        ],
      ];
    }

    // Button to go back to the step 1.
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::cancelFormStepTwo'],
      // Prevent validation errors as we are going back and the values from
      // the step 2 should be ignored then.
      '#limit_validation_errors' => [],
    ];

    // Submit the form, actually replacing the figures.
    // We default to base form submit and validation callbacks.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Import figures'),
    ];

    return $form;
  }

  /**
   * Get the figures table headers to display in the import confirmation step.
   *
   * @param array $columns
   *   Array of Header columns with the names as keys.
   *
   * @return array
   *   List of table headers.
   */
  public static function getTableHeaders(array $columns) {
    $headers = [];

    foreach (static::$columns as $name => $definition) {
      if (isset($columns[$name])) {
        $headers[$name] = $definition['label'];
      }
    }

    return $headers;
  }

  /**
   * Get the figures table rows to display in the import confirmation step.
   *
   * @param array $headers
   *   List of header column names.
   * @param array $figures
   *   List of figures data extracted from the spreadsheet.
   *
   * @return array
   *   Table rows. Each row contains cells for each column. Each cell can be
   *   either a string or a render array.
   */
  public static function getTableRows(array $headers, array $figures) {
    $rows = [];
    foreach ($figures as $data) {
      $row = [];
      foreach ($headers as $name => $label) {
        if (isset(static::$columns[$name]['table_display'])) {
          $row[$name] = static::call(static::$columns[$name]['table_display'] + [
            'name' => $name,
          ], $data);
        }
        else {
          $row[$name] = $data[$name] ?? '';
        }
      }
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Return to the first step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelFormStepTwo(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setValues($form_state->get('first_step_values'))
      ->set('step', 1)
      ->setRebuild(TRUE);
  }

  /**
   * Validate the form (after step 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to validate.
  }

  /**
   * Submit the form (after step 2).
   *
   * Generate the batch to update the "needs and requirements" figures.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch_size = $this->getSetting('batch_size', 50);

    // We will (optionally) delete the old figures and create the new ones
    // in batches as that takes time.
    $figures = $form_state->get('figures');
    if (!empty($figures['figures'])) {
      $operations = [];

      // We log the parsing errors (if any) during the batch process because
      // otherwise it slows down too much the parsing. Also it makes more sense
      // to log them when actually proceeding with the import.
      $operations[] = [
        __CLASS__ . '::logParsingInfo',
        [$figures['path'], $figures['errors']],
      ];

      // Extract the name of the figures to update/create.
      $names = array_map(function ($figure) {
        return $figure['name'];
      }, $figures['figures']);

      // Create batch steps to delete figure terms not present in the list.
      $records = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', 'needs_and_requirements')
        ->condition('name', $names, 'NOT IN')
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($records)) {
        foreach (array_chunk($records, $batch_size) as $ids) {
          $operations[] = [__CLASS__ . '::deleteFigureTerms', [$ids]];
        }
      }

      // Create batch steps to create the new figure terms.
      foreach (array_chunk($figures['figures'], $batch_size) as $data) {
        $operations[] = [__CLASS__ . '::createFigureTerms', [$data]];
      }

      $batch = [
        'title' => $this->t('Importing figures...'),
        'operations' => $operations,
        'finished' => __CLASS__ . '::batchFinished',
      ];
      batch_set($batch);
    }
    else {
      $this->messenger->addWarning($this->t('No figures to import. Existing figures will not be updated.'));
    }
  }

  /**
   * Log the parsing errors.
   *
   * @param string $path
   *   The path of the spreadsheet.
   * @param array $errors
   *   The parsing errors.
   * @param array $context
   *   The batch context.
   */
  public static function logParsingInfo($path, array $errors, array &$context) {
    // Log the filename to help make sense of the parsing errors.
    static::log(new FormattableMarkup('Parsed spreadsheet: @path', [
      '@path' => $path,
    ]), 'info');

    // We log the errors as notices because they don't impact the whole site.
    foreach ($errors as $error) {
      static::log($error, 'notice');
    }

    // Set a message for the current batch and update the progress status.
    $count = count($errors);
    $context['message'] = \Drupal::translation()->formatPlural($count,
      'Logged @count parsing error.',
      'Logged @count parsing errors.'
    );
    $context['results']['logged'][] = $count;
  }

  /**
   * Delete the "needs and requirements" figure terms for the given ids.
   *
   * Note: we don't actually delete the terms but wipe out their data and
   * unpublish them so that we can preserve any references to them in article
   * nodes for example, in case they are updated later on.
   *
   * @param array $ids
   *   Term ids.
   * @param array $context
   *   The batch context.
   */
  public static function deleteFigureTerms(array $ids, array &$context) {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Unpublish the terms that are not used.
    $terms = $storage->loadMultiple($ids);
    foreach ($terms as $term) {
      // Mark as unpublished.
      $term->status = 0;
      // Wipe out the values of the figures for the term.
      foreach (static::$columns as $field => $definition) {
        // The term name is a mandatory field that cannot be empty. It's also
        // the field we use when matching figures before creating a new one.
        if ($field !== 'name' && isset($definition['field'])) {
          $term->set($definition['field'], NULL);
        }
      }
      $term->save();
    }

    // Set a message for the current batch and update the progress status.
    $count = count($ids);
    $context['message'] = \Drupal::translation()->formatPlural($count,
      'Deleted @count old figure.',
      'Deleted @count old figures.'
    );
    $context['results']['deleted'][] = $count;
  }

  /**
   * Create new "needs and requirements" figure terms.
   *
   * Note: if a term already exists, we simply update it.
   *
   * @param array $figures
   *   Figure data.
   * @param array $context
   *   The batch context.
   */
  public static function createFigureTerms(array $figures, array &$context) {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Create a new term for each figure.
    foreach ($figures as $figure) {
      // Check if a term with the same name already exists and re-use it.
      $terms = $storage->loadByProperties([
        'vid' => 'needs_and_requirements',
        'name' => $figure['name'],
      ]);

      if (!empty($terms)) {
        $term = reset($terms);
      }
      else {
        $term = $storage->create(['vid' => 'needs_and_requirements']);
      }

      // Update the term fields.
      static::updateFigureTerm($term, $figure);
      // Mark as published.
      $term->status = 1;
      $term->save();
    }

    // Set a message for the current batch and update the progress status.
    $count = count($figures);
    $context['message'] = \Drupal::translation()->formatPlural($count,
      'Created/updated @count new figure.',
      'Created/updated @count new figures.'
    );
    $context['results']['created'][] = $count;
  }

  /**
   * Update a "needs and requirements" figure term's fields with new data.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Node to update.
   * @param array $data
   *   New figure data.
   *
   * @todo review if we should get the node fields via ::getFields() and
   * reset all the fields for which there is no corresponding data.
   */
  public static function updateFigureTerm(Term $term, array $data) {
    foreach (static::$columns as $name => $definition) {
      if (!isset($definition['field'])) {
        continue;
      }
      $field = $definition['field'];
      $value = NULL;
      if (isset($data[$name])) {
        $value = $data[$name];

        if (isset($definition['process'])) {
          $value = static::call($definition['process'] + ['name' => $name], $value);
        }
      }
      $term->set($field, $value);
    }
  }

  /**
   * Display message after the batch import is finished.
   *
   * @param bool $success
   *   Whether the batch process succeeeded or not.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   List of batch operations.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      if (!empty($results['deleted'])) {
        \Drupal::messenger()->addStatus(t('Deleted %deleted old figures.', [
          '%deleted' => array_sum($results['deleted']),
        ]));
      }
      if (!empty($results['created'])) {
        \Drupal::messenger()->addStatus(t('Created/updated %created figures.', [
          '%created' => array_sum($results['created']),
        ]));
      }
    }
    else {
      // @todo Show a more useful error message?
      \Drupal::messenger()->addError(t('No figures to import. Old figures were not deleted.'));
    }
  }

  /**
   * Get a taxonomy term ID. Create a new term if necessary.
   *
   * @param string $vocabulary
   *   Taxonomy vocabulary.
   * @param string|array $name
   *   Taxonomy term name or array of names.
   *
   * @return int|null
   *   Id of the first term, newly created if doesn't already exists.
   */
  public static function getTermId($vocabulary, $name) {
    $multiple = is_array($name);

    $names = array_filter(array_map('trim', $multiple ? $name : [$name]));

    if (empty($names)) {
      return NULL;
    }

    $results = [];
    foreach ($names as $name) {
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

      // Sanitize and truncate the term if necessary.
      $words = preg_split('/' . Unicode::PREG_CLASS_WORD_BOUNDARY . '/u', $name, -1, PREG_SPLIT_NO_EMPTY);
      if (count($words) > 10) {
        $name = implode(' ', array_slice($words, 0, 10)) . ' ...';
      }

      // Get any existing taxonomy term matching the given term name.
      $terms = $term_storage->loadByProperties([
        'vid' => $vocabulary,
        'name' => $name,
      ]);

      // Get the first existing term or create one.
      if (!empty($terms)) {
        $term = reset($terms);
      }
      else {
        $term = $term_storage->create([
          'vid' => $vocabulary,
          'name' => $name,
        ]);
        $term->save();
      }
      $results[] = $term->id();
    }

    return $multiple ? $results : reset($results);
  }

  /**
   * Extract figures from a spreadsheet.
   *
   * @param string $path
   *   File path.
   * @param int $max_rows
   *   Maximum number rows to parse.
   *
   * @return array
   *   Associative array with the spreadsheet file path, the header columns,
   *   figure list and potential parsing errors.
   */
  public static function parseSpreadsheet($path, $max_rows) {
    $columns = [];
    $figures = [];
    $errors = [];

    // We wrap this code in a try...catch because PHPSpreadsheet can throw
    // various exceptions when parsing a spreadsheet.
    try {
      // Get the worksheet to work with (pun intended).
      $sheet = static::getWorksheet($path);

      // Get the row to which will stop the parsing.
      $max_rows = min($sheet->getHighestDataRow(), $max_rows);

      // Parse the sheet, extracting figure data.
      $header_row_found = FALSE;
      foreach ($sheet->getRowIterator(1, $max_rows) as $row) {
        // Parse the row to see if it's the header one.
        if ($header_row_found === FALSE) {
          $data = static::parseHeaderRow($sheet, $row);
          // There are errors if the header row was found but some mandatory
          // columns are missing. In that case we abort the parsing.
          if (!empty($data['errors'])) {
            $errors = array_merge($errors, $data['errors']);
            break;
          }
          // Otherwise if the header row was found, we store the columns and
          // make sure we can start parsing the data. If not, we continue
          // looking for it.
          elseif (!empty($data['columns'])) {
            $columns = $data['columns'];
            $header_row_found = TRUE;
          }
        }
        // Parse a figure data row.
        else {
          $data = static::parseDataRow($columns, $sheet, $row);
          if (!empty($data['data'])) {
            // Log the errors only for "useful" rows with some data.
            if (!empty($data['errors'])) {
              $errors = array_merge($errors, $data['errors']);
            }
            // If there is an email, merge the data with the figure entry with
            // the same email address if any, otherwise create a new entry if
            // the data is "valid", meaning, it has all the mandatory fields.
            if (!empty($data['data']['name'])) {
              $figures[$data['data']['name']] = $data['data'];
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      $errors[] = $exception->getMessage();
    }

    return [
      'path' => $path,
      'columns' => $columns,
      'figures' => $figures,
      'errors' => $errors,
    ];
  }

  /**
   * Load a spreadsheet and return its first worksheet.
   *
   * @param string $path
   *   File path.
   *
   * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
   *   First worksheet.
   */
  public static function getWorksheet($path) {
    if (!file_exists($path)) {
      throw new \Exception("The spreadsheet file doesn't exist.");
    }

    // Get the spreadsheet type.
    $filetype = IOFactory::identify($path);

    // Create the spreadsheet reader.
    $reader = IOFactory::createReader($filetype);

    // Start reading the file.
    $spreadsheet = $reader->load($path);

    // We only deal with the first sheet.
    return $spreadsheet->getActiveSheet();
  }

  /**
   * Parse a row, attempting to determine if its the header row.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   Spreadsheet worksheet.
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Row $row
   *   Spreadsheet row.
   *
   * @return array
   *   Empty array if the row is not the header row, otherwise, return an
   *   associative array with the found columns which is an associative array
   *   with the column name (header) as value and the column letter as value.
   *   If the row is the header one but some mandatory columns are missing, the
   *   returnning array will have an `errors` key with the list of errors.
   */
  public static function parseHeaderRow(Worksheet $sheet, Row $row) {
    $columns = [];
    foreach ($row->getCellIterator() as $index => $cell) {
      $value = mb_strtolower(static::getCellValue($sheet, $cell->getCoordinate()));
      // Fix malformed column names...
      $value = trim(preg_replace('/\s+/u', ' ', $value));
      if (isset($value, static::$columns[$value]) && !isset($columns[$value])) {
        $columns[$value] = $cell->getColumn();
      }
      // If the first column doesn't have a name we assume it is the "name"
      // column with the overviews and appeals.
      elseif ($index === 'A') {
        $columns['name'] = $cell->getColumn();
      }
    }

    if (count($columns) < 2) {
      return [];
    }

    // Validate mandatory columns.
    $errors = [];
    foreach (static::$columns as $name => $definition) {
      if (!static::checkMandatoryField($name, $definition, $columns)) {
        // We use TranslatableMarkup so that the error can be displayed
        // translated in the confirmation step.
        $errors[] = new TranslatableMarkup('Missing @column column.', [
          '@column' => $name,
        ]);
      }
    }

    return [
      'columns' => $columns,
      'errors' => $errors,
    ];
  }

  /**
   * Parse a row with figure data.
   *
   * @todo Check if we need to use getCalculatedValue() instead of getValue().
   *
   * @param array $columns
   *   Associative array of header columns with their associated column index.
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   Spreadsheet worksheet.
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Row $row
   *   Spreadsheet row.
   *
   * @return array
   *   Associative array with the following keys:
   *   - data: associative array mapping the column names to their values,
   *   - errors: array with a list of parsing errors,
   *   - valid: a flag to indicate that the data contains all the mandatory
   *   fields.
   */
  public static function parseDataRow(array $columns, Worksheet $sheet, Row $row) {
    $index = $row->getRowIndex();
    $data = [];
    $errors = [];
    $valid = TRUE;

    // Get the data from the row foreach column with a recognized header.
    foreach ($columns as $name => $column) {
      $data[$name] = static::getCellValue($sheet, $column . $index);
    }

    // Skip if there no valid cell beside the name one as it's probably
    // not a data row.
    $skip = count(array_filter($data, function ($value, $name) {
      return $name !== 'name' && $value !== '';
    }, ARRAY_FILTER_USE_BOTH)) === 0;

    if ($skip) {
      return [];
    }

    // Process the row's data.
    foreach (static::$columns as $name => $definition) {
      if (isset($definition['preprocess'])) {
        if (!static::call($definition['preprocess'] + ['name' => $name], $data)) {
          $errors[$name] = new TranslatableMarkup('Invalid @column on row @row.', [
            '@column' => $name,
            '@row' => $index,
          ]);
        }
      }
    }

    // Check mandatory fields. This is not done in the loop above because
    // the data may change during preprocessing.
    foreach (static::$columns as $name => $definition) {
      if (!static::checkMandatoryField($name, $definition, $data)) {
        // No need to add different error messages for the same field, for
        // example if the field data was found invalid during preprocessing.
        if (!isset($errors[$name])) {
          $errors[$name] = new TranslatableMarkup('Missing @column on row @row.', [
            '@column' => $name,
            '@row' => $index,
          ]);
        }
        $valid = FALSE;
      }
    }

    return [
      'data' => $data,
      'errors' => array_values($errors),
      'valid' => $valid,
    ];
  }

  /**
   * Get a cell value.
   *
   * This extracts the value of a cell. If the cell is merged with other cells
   * we extract the combine value for the whole merge range.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   Spreadsheet worksheet.
   * @param string $reference
   *   Cell reference (ex: A1).
   *
   * @return string
   *   Extracted value, defaulting to an empty string.
   */
  public static function getCellValue(Worksheet $sheet, $reference) {
    static $references;
    static $values;

    if (!isset($references, $values)) {
      list($references, $values) = static::extractMergedCells($sheet);
    }

    if (isset($references[$reference])) {
      return $values[$references[$reference]];
    }
    elseif ($sheet->getCellCollection()->has($reference)) {
      return trim($sheet->getCellCollection()->get($reference)->getValue());
    }
    return '';
  }

  /**
   * Extract the values for the merged cells.
   *
   * We store the merged cells references and the merge range values so that
   * we don't have to parse the merge ranges every time we try to get a cell
   * value. This speeds tremendously the spreadsheet parsing at the cost of
   * increased memory usage.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   Worksheet from which to extract the merged cells data.
   *
   * @return array
   *   Array containing 2 elements: an associative array  of the references of
   *   the cells included in merge ranges mapped to to the range reference, and
   *   an associative array mapping range references to their values.
   */
  public static function extractMergedCells(Worksheet $sheet) {
    $references = [];
    $values = [];

    foreach ($sheet->getMergeCells() as $range) {
      // Extract all the merged cell references and store their mapping to
      // the merge range. We don't copy directly the merge range value to
      // reduce memory usage.
      foreach (Coordinate::extractAllCellReferencesInRange($range) as $index => $reference) {
        // The first cell of the range is supposed to contain the value of the
        // range.
        // @see \PhpOffice\PhpSpreadsheet\Cell\Cell::isMergeRangeValueCell()
        if ($index === 0) {
          if ($sheet->getCellCollection()->has($reference)) {
            $values[$range] = trim($sheet->getCellCollection()->get($reference)->getValue());
          }
          else {
            $values[$range] = '';
          }
        }
        $references[$reference] = $range;
      }
    }
    return [$references, $values];
  }

  /**
   * Preprocess the duty station country field.
   *
   * This extracts the countries from the `country` field or from the
   * `duty station country` field if the former is empty.
   *
   * @param string $name
   *   Field name.
   * @param array $data
   *   Row data.
   *
   * @return bool
   *   FALSE if the data was invalid, TRUE otherwise.
   */
  public static function preprocessNumber($name, array &$data) {
    if (isset($data[$name])) {
      $value = $data[$name];
      if ($value <= 0 || $value === '-' || strtoupper($value) === 'TBC') {
        $value = 0;
      }
      else {
        $options = ['options' => ['min_range' => 0]];
        $value = filter_var($data[$name], FILTER_VALIDATE_INT, $options);
      }
      // Remove the value if it's not a positive integer so that the row
      // will be removed and an error displayed.
      if ($value === FALSE) {
        unset($data[$name]);
        return FALSE;
      }
      else {
        $data[$name] = $value;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get a render array/string to display a formatted number (ex: 1.2 million).
   *
   * @param string $name
   *   Field name.
   * @param array $data
   *   Figure data.
   *
   * @return array|string
   *   Render array or empty string.
   */
  public static function displayNumber($name, array $data) {
    if (!isset($data[$name])) {
      return '';
    }
    if ($data[$name] == 0) {
      return 'TBC';
    }
    // @todo use the same formatter as the one used to render the figures in
    // the frontend (ex: 1.2 million).
    return number_format($data[$name]);
  }

  /**
   * Helper method to display a list of items as a HTML list.
   *
   * @param array $items
   *   List of strings.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   FormattableMarkup object containing the HTML list that can be passed
   *   as placeholder replacement to `t()`.
   */
  public static function formatList(array $items) {
    $html = '<ul><li>' . implode('</li><li>', $items) . '</li></ul>';
    return new FormattableMarkup($html, []);
  }

  /**
   * Check of the given data contains the mandatory field data.
   *
   * @param string $name
   *   Column name.
   * @param array $definition
   *   Column definition.
   * @param array $data
   *   Field data.
   *
   * @return bool
   *   Whether the field is present or not.
   */
  public static function checkMandatoryField($name, array $definition, array $data) {
    return empty($definition['mandatory']) || isset($data[$name]);
  }

  /**
   * Call a function with the given arguments and data.
   *
   * @param array $arguments
   *   Array with the callable function/method as first element and with the
   *   rest as parameters to pass to the callable.
   * @param mixed $data
   *   Additional data to pass to the callable. It is passed by reference and
   *   may be modified by the callable.
   *
   * @return mixed
   *   The result of the call.
   */
  public static function call(array $arguments, &$data) {
    $callable = array_shift($arguments);
    $arguments[] = &$data;
    return call_user_func_array($callable, array_values($arguments));
  }

  /**
   * Log a message.
   *
   * @param mixed $message
   *   String-ish value. If the message is an instance of
   *   \Drupal\Core\StringTranslation\TranslatableMarkup then we build a non
   *   translated message as the logs are for internal information and it
   *   doesn't make sense to have them in the display language of the current
   *   user.
   * @param string $level
   *   Log level.
   */
  public static function log($message, $level = 'info') {
    if ($message instanceof TranslatableMarkup) {
      $message = new FormattableMarkup($message->getUntranslatedString(), $message->getArguments());
    }
    \Drupal::logger('gho-figures-import')->log($level, $message);
  }

}
