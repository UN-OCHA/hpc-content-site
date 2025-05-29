<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\Content\Document;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\replicate\Replicator;

/**
 * Form alter class for the replicate confirm form.
 */
class ReplicateFormAlter implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content manager.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentSpaceManager $content_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSpaceManager = $content_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Alter the replicate confirm form.
   */
  public function alterForm(&$form, FormStateInterface $form_state): void {
    /** @var \Drupal\replicate_ui\Form\ReplicateConfirmForm $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    if (!$entity instanceof ContentSpaceAwareInterface) {
      return;
    }
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default');
    $form_state->set('form_display', $form_display);

    $form['new_label_en']['#title'] = $this->t('Label');
    $form['new_label_en']['#description'] = $this->t('This text will be used as the label of the replicated @type', [
      '@type' => strtolower($entity->type->entity->label()),
    ]);

    // Add the content space selector to select into which content type the
    // entity should be replicated.
    $widget = $form_display->getRenderer('field_content_space');
    if ($widget) {
      $items = $entity->get('field_content_space');
      $items->filterEmptyItems();
      $form['field_content_space'] = $widget->form($items, $form, $form_state);
      $form['field_content_space']['#weight'] = -1;
      $content_space_ids = $this->contentSpaceManager->getValidContentSpaceIdsForCurrentUser();
      $content_space_widget = &$form['field_content_space']['widget'];
      $content_space_widget['#options'] = array_intersect_key($content_space_widget['#options'], $content_space_ids + ['_none' => TRUE]);
      $content_space_widget['#default_value'] = $this->contentSpaceManager->getCurrentContentSpaceId();
      if (count($content_space_widget['#options']) == 1) {
        $content_space_widget['#disabled'] = TRUE;
      }

      array_unshift($form['actions']['submit']['#submit'], [
        self::class, 'submitNodeFormToContentSpace',
      ]);
    }

    if ($entity instanceof Document) {
      $form['replicate_content'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $form['replicate_content']['toggle'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Replicate document articles as well'),
        '#description' => $this->t('Checking this will create new copies of all the articles in the same content space as the replicated document. If unchecked, then a document replicated in the same content space will continue to link to the original articles, and a document replicated in a different content space will retain its chapters but will no longer contain any articles.'),
      ];
      $form['replicate_content']['suffix'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Article title suffix'),
        '#default_value' => '(Copy)',
        '#description' => $this->t('Leaving blank will create all the replicated articles with the same title as the original articles.'),
        '#states' => [
          'visible' => [
            ':input[name="replicate_content[toggle]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['actions']['submit']['#submit'][] = [
        self::class, 'submitProcessDocumentArticles',
      ];
    }

    // No description needed.
    $form['description']['#access'] = FALSE;

    // Cancel link should go back to the front page.
    $form['actions']['cancel']['#url'] = Url::fromRoute('<front>');

  }

  /**
   * Custom submit handler to set the current content space.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitNodeFormToContentSpace(array &$form, FormStateInterface $form_state): void {
    // Switch to the target content space if necessary. The content space is
    // set on the entity in ReplicateEventSubscriber::setContentSpace().
    if (self::submittedToDifferentContentSpace($form_state)) {
      $content_space_manager = self::getContentSpaceManager();
      $target_content_space = self::getSubmittedContentSpaceFromFormState($form_state);
      $content_space_manager->setCurrentContentSpaceId($target_content_space);
    }
  }

  /**
   * Custom submit handler to handle referenced articles.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitProcessDocumentArticles(array &$form, FormStateInterface $form_state): void {
    $replicate_content = $form_state->getValue(['replicate_content', 'toggle']);
    $article_suffix = trim($form_state->getValue(['replicate_content', 'suffix']) ?? '');

    // Get the reoplicated entity and check if it's a document.
    $replicated_entity = $form_state->get('replicated_entity');
    if (!$replicated_entity instanceof Document) {
      // We only support replication of referenced entities for documents.
      return;
    }

    if ($replicate_content) {
      // The chapters of the replicated docuument have been replicated by the
      // replicator, but we need to replicate all articles contained in each
      // chapter and replace them with the original articles, which also might
      // be in a different content space by now.
      $replicator = self::getReplicator();
      $articles_count = 0;
      foreach ($replicated_entity->getChapterParagraphs() as $chapter) {

        foreach ($chapter->getArticles() as $article) {
          $label_key = $article->getEntityType()->getKey('label');
          if (!empty($article_suffix)) {
            $article->set($label_key, $article->label() . ' ' . $article_suffix);
          }
          $replicated_article = $replicator->replicateEntity($article);
          $chapter->replaceArticle($article, $replicated_article);
          $chapter->save();
          $articles_count++;
        }
      }

      // Save the replicated document again to update the article references.
      $replicated_entity->setNewRevision(FALSE);
      $replicated_entity->setSyncing(TRUE);
      $replicated_entity->save();

      \Drupal::messenger()->addStatus(t('@count_articles articles have been replicated for %document_title', [
        '@count_articles' => $articles_count,
        '%document_title' => $replicated_entity->label(),
      ]));
    }

    // If the document has been replicated into a different content space and
    // content replication has not been requested, we need to remove all
    // contained articles.
    $different_content_space = self::submittedToDifferentContentSpace($form_state);
    if ($different_content_space && !$replicate_content) {
      foreach ($replicated_entity->getChapterParagraphs() as $chapter) {
        $chapter->removeArticles();
        $chapter->save();
      }
      // Save the replicated document again to update the article references.
      $replicated_entity->setNewRevision(FALSE);
      $replicated_entity->setSyncing(TRUE);
      $replicated_entity->save();
    }
  }

  /**
   * Check if the replication has been submitted to a different content space.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return bool
   *   TRUE if the content has been replicated into a different content space,
   *   FALSE otherwise.
   */
  private static function submittedToDifferentContentSpace(FormStateInterface $form_state): bool {
    if (!$form_state->has('different_content_space')) {
      $content_space_manager = self::getContentSpaceManager();
      $target_content_space = self::getSubmittedContentSpaceFromFormState($form_state);
      $form_state->set('different_content_space', $content_space_manager->getCurrentContentSpaceId() != $target_content_space);
    }
    return $form_state->get('different_content_space');
  }

  /**
   * Get the submitted content space from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return string
   *   The string value of the target content space.
   */
  private static function getSubmittedContentSpaceFromFormState(FormStateInterface $form_state): string {
    // Switch to the target content space if necessary.
    return $form_state->getValue([
      'field_content_space',
      0,
      'target_id',
    ]);
  }

  /**
   * Get the content space manager.
   *
   * @return \Drupal\ncms_ui\ContentSpaceManager
   *   The content space manager service.
   */
  private static function getContentSpaceManager(): ContentSpaceManager {
    return \Drupal::service('ncms_ui.content_space.manager');
  }

  /**
   * Get the content space manager.
   *
   * @return \Drupal\replicate\Replicator
   *   The replicator service.
   */
  private static function getReplicator(): Replicator {
    return \Drupal::service('replicate.replicator');
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['submitNodeFormToContentSpace'];
  }

}
