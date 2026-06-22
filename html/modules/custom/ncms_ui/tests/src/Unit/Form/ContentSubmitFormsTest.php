<?php

namespace Drupal\Tests\ncms_ui\Unit\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ncms_ui\Form\ContentSubmitConfirmForm;
use Drupal\ncms_ui\Form\ContentSubmitNoChangesAlertForm;
use Drupal\ncms_ui\Form\ContentSubmitNoTagsAlertForm;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests NCMS content submission confirmation forms.
 */
class ContentSubmitFormsTest extends UnitTestCase {

  /**
   * Tests the no-changes alert disables submission and closes via cancel.
   */
  public function testNoChangesAlertBuildForm(): void {
    $form = new ContentSubmitNoChangesAlertForm();
    $this->prepareConfirmForm($form);

    $build = $form->buildForm([], new FormState());

    $this->assertSame('content_submit_no_changes_alert_form', $form->getFormId());
    $this->assertSame('No changes have been made to the already published version. Please make some changes before publishing again.', (string) $build['description']['#markup']);
    $this->assertFalse($build['actions']['submit']['#access']);
    $this->assertContains('dialog-cancel', $build['actions']['cancel']['#attributes']['class']);
    $this->assertSame('Ok', (string) $build['actions']['cancel']['#title']);
  }

  /**
   * Tests the no-tags alert text when the content type starts with a consonant.
   */
  public function testNoTagsAlertUsesConsonantArticle(): void {
    $form = $this->createNoTagsAlertForm('report', 'Report');

    $question = (string) $form->getQuestion();

    $this->assertStringStartsWith('A report cannot be published without any tags associated with it.', $question);
  }

  /**
   * Tests the no-tags alert text when the content type starts with a vowel.
   */
  public function testNoTagsAlertUsesVowelArticle(): void {
    $form = $this->createNoTagsAlertForm('article', 'Article');

    $question = (string) $form->getQuestion();

    $this->assertStringStartsWith('An article cannot be published without any tags associated with it.', $question);
  }

  /**
   * Tests the no-tags alert form disables submission and uses cancel to close.
   */
  public function testNoTagsAlertBuildForm(): void {
    $form = $this->createNoTagsAlertForm('report', 'Report');

    $build = $form->buildForm([], new FormState());

    $this->assertSame('content_submit_no_tags_alert_form', $form->getFormId());
    $this->assertStringStartsWith('A report cannot be published without any tags associated with it.', (string) $build['description']['#markup']);
    $this->assertFalse($build['actions']['submit']['#access']);
    $this->assertContains('dialog-cancel', $build['actions']['cancel']['#attributes']['class']);
    $this->assertSame('Ok', (string) $build['actions']['cancel']['#title']);
  }

  /**
   * Tests confirm form hidden values and ajax submit metadata.
   */
  public function testSubmitConfirmBuildFormWithTriggeringElement(): void {
    $form = new ContentSubmitConfirmForm();
    $this->prepareConfirmForm($form);
    $entity = $this->mockNode(FALSE, 123);

    $build = $form->buildForm([], new FormState(), [
      '#ajax' => [
        'confirm_question' => 'Publish this version?',
        'confirm_field' => 'field_confirm_publish',
      ],
      '#attributes' => [
        'data-drupal-selector' => 'edit-submit',
      ],
    ], $entity);

    $this->assertSame('content_submit_confirm_form', $form->getFormId());
    $this->assertSame('Publish this version?', $build['description']['#markup']);
    $this->assertSame('field_confirm_publish', $build['confirm_field']['#value']);
    $this->assertSame('edit-submit', $build['submit_button']['#value']);
    $this->assertSame([$form, 'ajaxCallbackConfirm'], $build['actions']['submit']['#ajax']['callback']);
    $this->assertSame([
      'query' => [
        'ajax_form' => TRUE,
      ],
    ], $build['actions']['submit']['#ajax']['options']);
    $this->assertSame('entity.node_edit.submit_confirm', $build['actions']['submit']['#ajax']['url']->getRouteName());
    $this->assertSame(['node' => 123], $build['actions']['submit']['#ajax']['url']->getRouteParameters());
    $this->assertContains('dialog-cancel', $build['actions']['cancel']['#attributes']['class']);
  }

  /**
   * Tests ajax confirmation submits the original form controls in the browser.
   */
  public function testAjaxCallbackConfirmAddsSubmitCommands(): void {
    $form = new ContentSubmitConfirmForm();
    $form->setStringTranslation($this->getStringTranslationStub());
    $form_state = (new FormState())
      ->setValue('op', 'Ok')
      ->setUserInput([
        'confirm_field' => 'field_confirm_publish',
        'submit_button' => 'edit-submit',
      ]);

    $commands = $form->ajaxCallbackConfirm([], $form_state)->getCommands();

    $this->assertSame([
      ['command' => 'closeDialog', 'selector' => '#drupal-modal', 'persist' => FALSE],
      ['command' => 'invoke', 'selector' => '.is_changed', 'method' => 'removeClass', 'args' => ['is_changed']],
      ['command' => 'invoke', 'selector' => 'input[name="field_confirm_publish"]', 'method' => 'val', 'args' => [1]],
      [
        'command' => 'invoke',
        'selector' => 'input[data-drupal-selector="edit-submit"]',
        'method' => 'mousedown',
        'args' => [],
      ],
    ], $commands);
  }

  /**
   * Tests ajax cancellation only closes the dialog.
   */
  public function testAjaxCallbackWithoutConfirmationOnlyClosesDialog(): void {
    $form = new ContentSubmitConfirmForm();
    $form->setStringTranslation($this->getStringTranslationStub());
    $form_state = (new FormState())->setValue('op', 'Cancel');

    $commands = $form->ajaxCallbackConfirm([], $form_state)->getCommands();

    $this->assertSame([
      ['command' => 'closeDialog', 'selector' => '#drupal-modal', 'persist' => FALSE],
    ], $commands);
  }

  /**
   * Adds the framework services ConfirmFormBase needs to build cancel links.
   */
  private function prepareConfirmForm(ConfirmFormBase $form): void {
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/node/1'));

    $form->setStringTranslation($this->getStringTranslationStub());
    $form->setRequestStack($request_stack);
  }

  /**
   * Creates a no-tags form with controlled route and node type storage.
   */
  private function createNoTagsAlertForm(string $bundle, string $label): TestContentSubmitNoTagsAlertForm {
    $node_type = $this->createMock(NodeTypeInterface::class);
    $node_type->method('label')->willReturn($label);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with($bundle)->willReturn($node_type);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node_type')->willReturn($storage);

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRawParameter')->with('node_type')->willReturn($bundle);

    $form = new TestContentSubmitNoTagsAlertForm();
    $this->prepareConfirmForm($form);
    $form->setRouteMatchForTest($route_match);
    $form->setEntityTypeManagerForTest($entity_type_manager);
    return $form;
  }

  /**
   * Mocks a node for confirm form route generation.
   */
  private function mockNode(bool $is_new, int $id): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isNew')->willReturn($is_new);
    $node->method('id')->willReturn($id);
    return $node;
  }

}

/**
 * Testable no-tags form with explicit setters for framework-owned state.
 */
class TestContentSubmitNoTagsAlertForm extends ContentSubmitNoTagsAlertForm {

  /**
   * Sets the route match used by FormBase::getRouteMatch().
   */
  public function setRouteMatchForTest(RouteMatchInterface $route_match): void {
    $this->routeMatch = $route_match;
  }

  /**
   * Sets the node type storage dependency normally injected by create().
   */
  public function setEntityTypeManagerForTest(EntityTypeManagerInterface $entity_type_manager): void {
    $this->entityTypeManager = $entity_type_manager;
  }

}
