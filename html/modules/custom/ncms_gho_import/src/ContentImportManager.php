<?php

namespace Drupal\ncms_gho_import;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Helper class for content import.
 */
class ContentImportManager {

  /**
   * A list of paragraph types that can be safely removed.
   */
  private const OBSOLETE_PARAGRAPH_TYPES = [
    'needs_and_requirements',
    'section_index',
  ];

  /**
   * The title for the GHO 2021 document node.
   */
  private const DOCUMENT_TITLE = 'Global Humanitarian Overview 2021';

  /**
   * The short title for the GHO 2021 document node.
   */
  private const DOCUMENT_SHORT_TITLE = 'GHO 2021';

  /**
   * The chapter map for the document.
   */
  private const CHAPTER_MAP = [
    'Introduction' => [
      'title' => 'Introduction',
      'short_title' => 'Introduction',
    ],
    'Part one: Global trends' => [
      'title' => 'Part one: Global Trends',
      'short_title' => 'Global trends',
    ],
    'Part two: Inter-agency coordinated appeals' => [
      'title' => 'Part two: Inter-Agency Coordinated Appeals',
      'short_title' => 'Response plans',
    ],
    'Part three: Delivering better' => [
      'title' => 'Part three: Delivering Better',
      'short_title' => 'Delivering better',
    ],
    'hidden' => [
      'title' => 'Country/regional articles',
      'short_title' => 'Country/regional articles',
    ],
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The GHO 2021 document entity.
   *
   * @var \Drupal\ncms_ui\Entity\Content\Document
   */
  protected $document;

  /**
   * Public constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
    $this->time = $time;
    $this->document = $this->assureDocument();
  }

  /**
   * Get the document.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Document|null
   *   The GHO 2021 document entity or NULL.
   */
  private function getDocument() {
    $storage = $this->entityTypeManager->getStorage('node');
    $documents = $storage->loadByProperties([
      'type' => 'document',
      'title' => self::DOCUMENT_TITLE,
    ]);
    return reset($documents) ?: NULL;
  }

  /**
   * Create the document.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Document|null
   *   The GHO 2021 document entity or NULL.
   */
  private function createDocument() {
    if ($this->getDocument()) {
      return;
    }
    $storage = $this->entityTypeManager->getStorage('node');
    $chapter_paragraphs = [];
    foreach (self::CHAPTER_MAP as $key => $chapter) {
      /** @var \Drupal\paragraphs\ParagraphInterface $chapter_paragraph */
      $chapter_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
        'type' => 'document_chapter',
        'field_title' => $chapter['title'],
        'field_short_title' => $chapter['short_title'],
        'field_articles' => [],
      ]);
      if ($key == 'hidden') {
        $chapter_paragraph->get('field_hide_from_navigation')->setValue(TRUE);
      }
      $chapter_paragraph->save();
      $chapter_paragraphs[] = $chapter_paragraph;
    }
    $document = $storage->create([
      'type' => 'document',
      'title' => self::DOCUMENT_TITLE,
      'field_short_title' => self::DOCUMENT_SHORT_TITLE,
      'status' => NodeInterface::PUBLISHED,
      'field_paragraphs' => array_map(function ($paragraph) {
        return [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }, $chapter_paragraphs),
    ]);
    $document->save();
    /** @var \Drupal\paragraphs\ParagraphInterface[] $chapter_paragraphs */
    foreach ($chapter_paragraphs as $paragraph) {
      $paragraph->setParentEntity($document, 'field_paragraphs');
      $paragraph->save();
    }
    $this->setContentSpace($document);
    $this->setContentTags($document);
    $document->save();
    return $document;
  }

  /**
   * Import the document image.
   *
   * @param array $content
   *   The source data of an article.
   */
  public function importDocumentImage($content) {
    /** @var \Drupal\ncms_ui\Entity\Content\Document $document */
    $document = $this->document;
    $media = $content['custom_fields']['field_hero_image'][0] ?? NULL;
    if (!$media) {
      return;
    }
    $uri = $this->downloadMediaImage($media);
    $file = $uri ? $this->getFileByUri($uri) : NULL;
    if (!$file) {
      return;
    }
    $caption = $content['custom_fields']['field_caption'][0] ?? NULL;
    $document->get('field_caption')->setValue([
      'first' => $caption['first'],
      'second' => $caption['second'],
    ]);

    $image_data = $media['custom_fields']['field_media_image'][0];
    $entity = $this->assureMediaImageForFile($file);
    $entity->url = $image_data['url'];
    $entity->field_media_image->alt = $image_data['alt'];
    $entity->field_media_image->title = $image_data['title'];
    $entity->setSyncing(TRUE);
    $entity->save();

    $document->get('field_hero_image')->setValue($entity);
    $document->setNewRevision(FALSE);
    $document->save();
  }

  /**
   * Assure that the GHO 2021 document exists.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Document
   *   The GHO 2021 document entity.
   */
  private function assureDocument() {
    $document = $this->getDocument();
    return $document ?? $this->createDocument();
  }

  /**
   * Add the article to the document.
   *
   * Note that the order of the articles is arbitrary and needs to be
   * corrected manually.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   * @param array $content
   *   An array containing the content.
   */
  public function addArticleToDocument(Article &$entity, array $content) {
    if ($entity->isNew() && !$entity->id()) {
      return;
    }
    $should_be_added = !empty($content['base_fields']['menu_link']);
    $section_name = $content['custom_fields']['field_section'][0]['base_fields']['name'] ?? NULL;
    if ($should_be_added && !array_key_exists($section_name, self::CHAPTER_MAP)) {
      return;
    }

    /** @var \Drupal\ncms_ui\Entity\Content\Document $document */
    $document = $this->document;
    $chapter_label = $should_be_added ? self::CHAPTER_MAP[$section_name]['title'] : 'Country/regional articles';

    $chapters = $document->get('field_paragraphs')->referencedEntities();
    foreach ($chapters as $chapter) {
      // First make sure we have the right chapter.
      if ($chapter->get('field_title')->value != $chapter_label) {
        continue;
      }
      $articles = $chapter->get('field_articles')->getValue();

      // Remove the article from the chapter.
      $articles = array_filter($articles, function ($item) use ($entity) {
        return $item['target_id'] != $entity->id();
      });
      // Add the article to the chapter.
      $articles[] = $entity;

      $chapter->set('field_articles', $articles);
      $chapter->save();
      break;
    }
  }

  /**
   * Set the content space for the imported content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   */
  public function setContentSpace(ContentEntityInterface &$entity) {
    if (!$entity instanceof ContentInterface) {
      return;
    }
    $content_space_name = $this->isMonthlyUpdate($entity) ? 'GHO Monthly update' : 'Global';
    $content_space = $this->loadTermByName($content_space_name, 'content_space');
    if (!$content_space) {
      return;
    }
    $entity->get('field_content_space')->setValue([
      // Global content space.
      'target_id' => $content_space->id(),
    ]);
  }

  /**
   * Set the content tags.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   */
  public function setContentTags(&$entity) {
    if (!$entity instanceof ContentInterface) {
      return;
    }
    if ($document_type = $this->loadTermByName('GHO', 'document_type')) {
      $entity->get('field_document_type')->setValue(['target_id' => $document_type->id()]);
    }
    if ($year = $this->loadTermByName('2021', 'year')) {
      $entity->get('field_year')->setValue(['target_id' => $year->id()]);
    }
    if ($this->isMonthlyUpdate($entity)) {
      if ($document_type = $this->loadTermByName('GHO Monthly', 'document_type')) {
        $entity->get('field_document_type')->setValue(['target_id' => $document_type->id()]);
      }
      [,,, $month] = explode(' ', $entity->label());
      if ($month_term = $this->loadTermByName($month, 'month')) {
        $entity->get('field_month')->setValue(['target_id' => $month_term->id()]);
      }
    }
  }

  /**
   * Set some properties for the imported content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   * @param array $content
   *   An array containing the content.
   */
  public function setContentProperties(ContentEntityInterface &$entity, array &$content) {
    if (!$entity instanceof ContentInterface) {
      return;
    }
    $entity->get('field_short_title')->setValue($entity->label());

    // Set the status here manually by using the proper methods. This assures
    // that \Drupal\ncms_ui\Entity\Content\ContentBase is involved and
    // correctly sets the moderation status.
    $content['base_fields']['status'] ? $entity->setPublished() : $entity->setUnpublished();

    $entity->setChangedTime($this->time->getRequestTime());

    if ($this->isMonthlyUpdate($entity)) {
      unset($content['custom_fields']['field_pdf']);
    }
  }

  /**
   * Update the hero image.
   *
   * @param array $content
   *   An array containing the content.
   */
  public function updateHeroImage(array &$content) {
    // Get the hero image.
    $media_items = $content['custom_fields']['field_hero_image'] ?? [];
    $this->processMediaItems($media_items);
    $content['custom_fields']['field_hero_image'] = $media_items;
  }

  /**
   * Update paragraph types from the incoming content array.
   *
   * @param array $content
   *   An array containing the content.
   */
  public function updateParagraphs(array &$content) {
    if (empty($content['custom_fields']['field_paragraphs'])) {
      return;
    }
    foreach ($content['custom_fields']['field_paragraphs'] as $key => &$paragraph) {
      // Skip paragraphs in disabled regions.
      $region = $paragraph['base_fields']['behavior_settings']['layout_paragraphs']['region'] ?? NULL;
      if ($region == '_disabled') {
        unset($content['custom_fields']['field_paragraphs'][$key]);
        continue;
      }

      switch ($paragraph['bundle']) {
        case 'achievement':
          $this->updateAchievement($paragraph, $content);
          break;

        case 'bottom_figure_row':
          $this->updateTopFiguresSmall($paragraph);
          break;

        case 'needs_and_requirements':
          $this->updateTopFigures($paragraph);
          break;

        case 'image_with_text':
          $this->updateImageWithText($paragraph);
          break;

        case 'interactive_content':
          $this->updateInteractiveContent($paragraph);
          break;

        case 'photo_gallery':
          $this->updatePhotoGallery($paragraph);
          break;

        case 'story':
          $this->updateStory($paragraph);
          break;

        case 'sub_article':
          foreach ($paragraph['custom_fields']['field_article'] as &$article) {
            $this->updateParagraphs($article);
          }
          break;
      }
    }
  }

  /**
   * Remove obsolete paragraph types from the incoming content array.
   *
   * @param array $content
   *   An array containing the content.
   */
  public function removeObsoleteParagraphs(array &$content) {
    if (empty($content['custom_fields']['field_paragraphs'])) {
      return;
    }
    foreach ($content['custom_fields']['field_paragraphs'] as $key => $item) {
      if (in_array($item['bundle'], self::OBSOLETE_PARAGRAPH_TYPES)) {
        $this->deleteParagraph($item);
        unset($content['custom_fields']['field_paragraphs'][$key]);
        continue;
      }
      if ($item['bundle'] == 'sub_article') {
        foreach ($item['custom_fields']['field_article'] as &$article) {
          $this->removeObsoleteParagraphs($article);
        }
      }
    }
  }

  /**
   * Update a paragraph of type achievement to achievement_list.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   * @param array $content
   *   An array containing the full source content.
   */
  private function updateAchievement(&$paragraph, array &$content) {
    $achievement_list_key = array_key_first(array_filter($content['custom_fields']['field_paragraphs'], function ($item) {
      return $item['bundle'] == 'achievement_list';
    }));
    if (!$achievement_list_key) {
      $storage = $this->entityTypeManager->getStorage('paragraph');
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $achievement_list_paragraph = $storage->create([
        'type' => 'achievement_list',
        'status' => TRUE,
        'field_achievements' => [],
        'field_text' => NULL,
        'field_columns' => 2,
      ]);
      $achievement_list_paragraph->setSyncing(TRUE);
      $achievement_list_paragraph->save();
      $achievement_list = [
        'uuid' => $achievement_list_paragraph->uuid(),
        'entity_type' => 'paragraph',
        'bundle' => 'achievement_list',
        'base_fields' => $paragraph['base_fields'],
        'custom_fields' => [
          'field_achievements' => [],
          'field_text' => NULL,
          'field_columns' => ['value' => 2],
        ],
      ];
      $content['custom_fields']['field_paragraphs'][] = &$achievement_list;
    }
    else {
      $achievement_list = &$content['custom_fields']['field_paragraphs'][$achievement_list_key];
    }
    $achievement_list['custom_fields']['field_achievements'] = array_filter($achievement_list['custom_fields']['field_achievements'], function ($item) use ($paragraph) {
      return $item['uuid'] != $paragraph['uuid'];
    });

    // Get the summary.
    $summary_paragraph_key = array_key_first(array_filter($paragraph['custom_fields']['field_achievement'][0]['custom_fields']['field_paragraphs'], function ($item) {
      return $item['bundle'] == 'text';
    }));
    $summary_paragraph = $paragraph['custom_fields']['field_achievement'][0]['custom_fields']['field_paragraphs'][$summary_paragraph_key];
    $summary_text = strip_tags(html_entity_decode($summary_paragraph['custom_fields']['field_text'][0]['value'] ?? ''));
    $summary_text = str_replace([PHP_EOL, "\r\n", "\r", "\n"], ' ', $summary_text);
    $summary_text = preg_replace('/\[([1-9][0-9]*)\]/', '', $summary_text);
    $summary_text = preg_replace('/\s+/', ' ', $summary_text);

    // Try to extract sources from the footnotes.
    $summary_footnotes = strip_tags(html_entity_decode($summary_paragraph['custom_fields']['field_footnotes'][0]['value'] ?? ''));
    $summary_footnotes = str_replace([PHP_EOL, "\r\n", "\r", "\n"], PHP_EOL, $summary_footnotes);
    $summary_footnotes = preg_replace('/\[([1-9][0-9]*)\]/', '', $summary_footnotes);
    $footnotes = array_filter(explode(PHP_EOL, $summary_footnotes));
    $sources = [];
    foreach ($footnotes as $footnote) {
      [$source] = explode(',', $footnote);
      if (!in_array($source, $sources)) {
        $sources[] = $source;
      }
    }
    sort($sources);

    $achievement_list['custom_fields']['field_achievements'][] = [
      'uuid' => $paragraph['uuid'],
      'entity_type' => 'paragraph',
      'bundle' => 'achievement',
      'base_fields' => $paragraph['base_fields'],
      'custom_fields' => [
        'field_icon' => $paragraph['custom_fields']['field_achievement'][0]['custom_fields']['field_icon'],
        'field_title' => ['value' => $paragraph['custom_fields']['field_achievement'][0]['base_fields']['title']],
        'field_summary' => [
          'value' => $summary_text,
        ],
        'field_source' => ['value' => implode(', ', $sources)],
      ],
    ];
    $content['custom_fields']['field_paragraphs'] = array_filter($content['custom_fields']['field_paragraphs'], function ($item) use ($paragraph) {
      return $item['uuid'] != $paragraph['uuid'];
    });
  }

  /**
   * Update a paragraph of type needs_and_requirements to top_figures.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updateTopFigures(array &$paragraph) {
    $figures = $paragraph['custom_fields']['field_needs_and_requirements'][0]['custom_fields'] ?? [];
    if (empty($figures)) {
      return;
    }
    $paragraph['custom_fields']['field_figures'] = [];
    $paragraph['custom_fields']['field_figures'][] = [
      'label' => 'People in need',
      'value' => $this->formatNumber($figures['field_people_in_need'][0]['value']),
    ];
    $paragraph['custom_fields']['field_figures'][] = [
      'label' => 'People targeted',
      'value' => $this->formatNumber($figures['field_people_targeted'][0]['value']),
    ];
    $paragraph['custom_fields']['field_figures'][] = [
      'label' => 'Requirements (US$)',
      'value' => $this->formatNumber($figures['field_requirements'][0]['value']),
    ];
    $paragraph['bundle'] = 'top_figures';
    unset($paragraph['custom_fields']['field_needs_and_requirements']);

    $this->replaceParagraph($paragraph, $this->createTopFiguresFromParagraph($paragraph));
  }

  /**
   * Update a paragraph of type bottom_figure_row to top_figures_small.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updateTopFiguresSmall(array &$paragraph) {
    $figures = $paragraph['custom_fields']['field_bottom_figures'];
    if (empty($figures)) {
      $this->deleteParagraph($paragraph);
      return;
    }
    $paragraph['custom_fields']['field_figures'] = [];
    foreach ($figures as $figure) {
      $paragraph['custom_fields']['field_figures'][] = [
        'label' => $figure['first'],
        'value' => $figure['second'],
      ];
    }
    $paragraph['bundle'] = 'top_figures_small';
    unset($paragraph['custom_fields']['field_bottom_figures']);

    $this->replaceParagraph($paragraph, $this->createTopFiguresFromParagraph($paragraph));
  }

  /**
   * Update a paragraph of type photo_gallery.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updatePhotoGallery(array &$paragraph) {
    $media_items = $paragraph['custom_fields']['field_photos'] ?? [];
    $this->processMediaItems($media_items);
    $paragraph['custom_fields']['field_photos'] = $media_items;
  }

  /**
   * Update a paragraph of type photo_gallery.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updateImageWithText(array &$paragraph) {
    $media_items = $paragraph['custom_fields']['field_image'] ?? [];
    $this->processMediaItems($media_items);
    $paragraph['custom_fields']['field_image'] = $media_items;
  }

  /**
   * Update a paragraph of type interactive_content.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updateInteractiveContent(array &$paragraph) {
    $media_items = $paragraph['custom_fields']['field_image'] ?? [];
    $this->processMediaItems($media_items);
    $paragraph['custom_fields']['field_image'] = $media_items;

    $paragraph['custom_fields']['field_show_interactive_content'] = $paragraph['custom_fields']['field_show_datawrapper'] ?? ['value' => TRUE];
    unset($paragraph['custom_fields']['field_show_datawrapper']);
  }

  /**
   * Update a paragraph of type story.
   *
   * @param array $paragraph
   *   The data of the original paragraph in the import source.
   */
  private function updateStory(array &$paragraph) {
    $media_items = $paragraph['custom_fields']['field_story'][0]['custom_fields']['field_media'] ?? [];
    $this->processMediaItems($media_items);
    $paragraph['custom_fields']['field_story'][0]['custom_fields']['field_media'] = $media_items;
  }

  /**
   * Replace a paragraph entity.
   *
   * @param array $paragraph_data
   *   An array with the paragraph source data.
   * @param \Drupal\paragraphs\ParagraphInterface $new_paragraph
   *   The new paragraph entity that is replacing the old one.
   */
  private function replaceParagraph(&$paragraph_data, $new_paragraph) {
    $this->deleteParagraph($paragraph_data);
    $paragraph_data['uuid'] = $new_paragraph->uuid();
  }

  /**
   * Delete a paragraph.
   *
   * @param array $paragraph_data
   *   An array with the paragraph source data.
   */
  private function deleteParagraph($paragraph_data) {
    $this->loadParagraphByUuid($paragraph_data['uuid'])?->delete();
  }

  /**
   * Create a top figures paragraph from the given paragraph data.
   *
   * @param array $paragraph_data
   *   The original paragraph source data.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The newly created paragraph.
   */
  private function createTopFiguresFromParagraph(array $paragraph_data) {
    $storage = $this->entityTypeManager->getStorage('paragraph');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $storage->create([
      'type' => $paragraph_data['bundle'],
      'status' => $paragraph_data['base_fields']['status'],
      'field_figures' => $paragraph_data['custom_fields']['field_figures'],
      'field_dataset' => $paragraph_data['custom_fields']['field_dataset'] ?? NULL,
    ]);
    $paragraph->setBehaviorSettings('promoted_behavior', $paragraph_data['base_fields']['behavior_settings']['promoted_behavior'] ?? []);
    $paragraph->setSyncing(TRUE);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Load a paragraph by it's uuid.
   *
   * @param string $uuid
   *   The UUID of the paragraph to load.
   *
   * @return \Drupal\paragraphs\ParagraphInterface|null
   *   The paragraph entity or NULL.
   */
  private function loadParagraphByUuid($uuid) {
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $result = $storage->loadByProperties(['uuid' => $uuid]);
    return count($result) == 1 ? reset($result) : NULL;
  }

  /**
   * Load a term by name and vocabulary.
   *
   * @param string $name
   *   The name of the term.
   * @param string $vid
   *   The vocabulary id.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   A taxonomy term object or NULL.
   */
  private function loadTermByName($name, $vid) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vid,
      'name' => $name,
    ]);
    return count($terms) == 1 ? reset($terms) : NULL;
  }

  /**
   * Check if the given entity is a monthly update.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if it is a monthly update, FALSE otherwise.
   */
  private function isMonthlyUpdate(ContentInterface $entity) {
    return str_starts_with($entity->label(), 'Global Humanitarian Overview') and str_ends_with($entity->label(), 'Update');
  }

  /**
   * Format a number with a unit.
   *
   * @param mixed $value
   *   A value that can be cast to an integer.
   *
   * @return mixed
   *   Either a string with the formatted number, or the original value.
   */
  private function formatNumber($value) {
    if ($value < 1000) {
      return $value;
    }
    $units = ['', 'thousend', 'million', 'billion', 'trillion'];
    $log = floor(log($value, 1000));
    $pow = pow(1000, $log);
    return trim(round($value / $pow, 2) . ' ' . $units[$log]);
  }

  /**
   * Process media items and download the images.
   *
   * @param array $media_items
   *   An array of media item arrays from the source data.
   */
  private function processMediaItems(array &$media_items) {
    foreach ($media_items as $key => &$media_item) {
      $local_uri = $this->downloadMediaImage($media_item);
      if (!$local_uri) {
        unset($media_items[$key]);
      }
    }
  }

  /**
   * Download an image that is uploaded as a media entity.
   *
   * @param array $media
   *   The array describing the media entity.
   *
   * @return string|false
   *   A string with the path of the resulting file, or FALSE on error.
   */
  private function downloadMediaImage(array &$media) {
    if ($media['bundle'] != 'image') {
      return FALSE;
    }
    $image = &$media['custom_fields']['field_media_image'][0] ?? NULL;
    if (!$image || empty($image['url'])) {
      return FALSE;
    }

    $uri = $this->downloadImage($image['url'], $image['uri']);
    if ($uri) {
      $image['uri'] = $uri;
      $image['url'] = $this->fileUrlGenerator->generateAbsoluteString($uri);
    }
    return $uri;
  }

  /**
   * Download the given image from the given URL to the destination.
   *
   * @param string $url
   *   The URL of the remote image.
   * @param string $destination
   *   The destination. This must be a stream wrapper.
   *
   * @return string|false
   *   A string with the path of the resulting file, or FALSE on error.
   */
  private function downloadImage($url, $destination) {
    $url = $this->getRemoteUrl($url);
    if ($this->getFileByUri($destination) && file_exists($destination)) {
      return $destination;
    }
    elseif ($this->remoteImageUrlExists($url)) {
      // Download the image to the local file system and rewrite the url for
      // the media item.
      return $this->fileSystem->saveData(file_get_contents($url), $destination, FileExists::Replace);
    }
    else {
      $style_candidates = [
        'full_width_2_1_246',
        'full_width_16_9_200',
        'full_width_200',
        'full_width_2_1_210',
        'full_width_2_1_180',
      ];
      foreach ($style_candidates as $style_id) {
        // Create a fallback URL to the image style derivative that is most
        // likely to exist and should be big enough to serve as a base image.
        $fallback_url = $this->getFallbackUrl($style_id, $url);
        if (!$this->remoteImageUrlExists($fallback_url)) {
          continue;
        }
        // Download the fallback image, which is the same image just in the
        // form of an image style derivative.
        return $this->fileSystem->saveData(file_get_contents($fallback_url), $destination, FileExists::Replace);
      }
    }
    return FALSE;
  }

  /**
   * Get the correct remote URL for an image URL.
   *
   * @param string $url
   *   The original URL from the import source.
   *
   * @return string
   *   The remote URL for the GHO 2021 archive site.
   */
  private function getRemoteUrl($url) {
    $offset = strpos($url, '/sites/default/files');
    return $offset ? 'https://archive.2021.gho.unocha.org' . substr($url, $offset) : FALSE;
  }

  /**
   * Get a fallback URL for an image.
   *
   * @param string $style_id
   *   The style id.
   * @param string $url
   *   The original image URL.
   *
   * @return string
   *   The fallback URL.
   */
  private function getFallbackUrl($style_id, $url) {
    return str_replace('files/images', 'files/styles/' . $style_id . '/public/images', $url);
  }

  /**
   * Check if a remote image url exists.
   *
   * @param string $url
   *   The URL string for the image.
   *
   * @return bool
   *   TRUE if the image exists, FALSE otherwise.
   */
  private function remoteImageUrlExists($url) {
    $headers = @get_headers($url, TRUE);
    return $headers ? str_contains($headers[0], '200 OK') && !empty($headers['Content-Type']) : FALSE;
  }

  /**
   * Load a file by it's URI.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity or NULL.
   */
  private function getFileByUri($uri) {
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties([
      'uri' => $uri,
    ]);
    return count($files) == 1 ? reset($files) : NULL;
  }

  /**
   * Load a media entity for the given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media entity or NULL.
   */
  private function assureMediaImageForFile(FileInterface $file) {
    $properties = [
      'bundle' => 'image',
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ];
    $images = $this->entityTypeManager->getStorage('media')->loadByProperties($properties);
    if (count($images) == 1) {
      reset($images);
    }
    // Otherwise create a new entity.
    return $this->entityTypeManager->getStorage('media')->create($properties);
  }

}
