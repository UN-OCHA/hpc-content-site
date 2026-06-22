<?php

namespace Drupal\Tests\ncms_ui\Unit\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Form\ContentSpaceSelectForm;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the content space selector form.
 */
class ContentSpaceSelectFormTest extends UnitTestCase {

  /**
   * Tests the selector groups user and global content spaces.
   */
  public function testBuildFormGroupsContentSpaces(): void {
    $form = $this->createForm(
      $this->mockContentSpaceManager([
        1 => 'Country offices',
        2 => 'Global content',
      ], [1], 1, TRUE),
      $this->mockCurrentPath('/admin/content'),
      $this->createMock(CacheBackendInterface::class)
    );
    $form_state = (new FormState())->setUserInput(['current_path' => '/admin/content']);

    $build = $form->buildForm([], $form_state);

    $this->assertSame('ncms_ui_content_space_select_form', $form->getFormId());
    $this->assertSame('/admin/content', $build['current_path']['#value']);
    $this->assertSame([
      'My content spaces' => [1 => 'Country offices'],
      'Other content spaces' => [2 => 'Global content'],
    ], $build['content_space']['#options']);
    $this->assertSame(1, $build['content_space']['#default_value']);
    $this->assertFalse($build['content_space']['#disabled']);
    $this->assertSame(['ncms_ui/throbber'], $build['content_space']['#attached']['library']);
  }

  /**
   * Tests submitted content space changes invalidate render cache.
   */
  public function testBuildFormStoresSubmittedContentSpace(): void {
    $manager = $this->mockContentSpaceManager([1 => 'Country offices'], [1], 1, TRUE);
    $manager->expects($this->once())
      ->method('setCurrentContentSpaceId')
      ->with(1);

    $render_cache = $this->createMock(CacheBackendInterface::class);
    $render_cache->expects($this->once())
      ->method('invalidateAll');

    $form = $this->createForm($manager, $this->mockCurrentPath('/admin/content'), $render_cache);
    $form_state = (new FormState())->setValue('content_space', 1);

    $form->buildForm([], $form_state);
  }

  /**
   * Tests the selector is disabled outside content-space restricted paths.
   */
  public function testBuildFormDisablesSelectorOnUnrestrictedPath(): void {
    $form = $this->createForm(
      $this->mockContentSpaceManager([1 => 'Country offices'], [1], 1, FALSE),
      $this->mockCurrentPath('/node/1'),
      $this->createMock(CacheBackendInterface::class)
    );

    $build = $form->buildForm([], new FormState());

    $this->assertTrue($build['content_space']['#disabled']);
  }

  /**
   * Tests the ajax callback shows the fullscreen throbber before redirecting.
   */
  public function testAjaxCallback(): void {
    $form = new ContentSpaceSelectForm();
    $build = [];
    $form_state = (new FormState())->setValue('current_path', '/admin/content/articles');

    $commands = $form->ajaxCallback($build, $form_state)->getCommands();

    $this->assertSame('insert', $commands[0]['command']);
    $this->assertSame('append', $commands[0]['method']);
    $this->assertSame('body', $commands[0]['selector']);
    $this->assertStringContainsString('ajax-progress--fullscreen', $commands[0]['data']);
    $this->assertSame([
      'command' => 'redirect',
      'url' => '/admin/content/articles',
    ], $commands[1]);
  }

  /**
   * Creates the form through its normal container factory.
   */
  private function createForm(ContentSpaceManager $manager, CurrentPathStack $current_path, CacheBackendInterface $render_cache): ContentSpaceSelectForm {
    $container = new ContainerBuilder();
    $container->set('ncms_ui.content_space.manager', $manager);
    $container->set('path.current', $current_path);
    $container->set('cache.render', $render_cache);

    $form = ContentSpaceSelectForm::create($container);
    $form->setStringTranslation($this->getStringTranslationStub());
    return $form;
  }

  /**
   * Mocks the content space manager methods used while building the selector.
   */
  private function mockContentSpaceManager(array $spaces, array $user_space_ids, int $current_space_id, bool $can_change): ContentSpaceManager {
    $terms = [];
    foreach ($spaces as $id => $label) {
      $terms[$id] = $this->mockContentSpaceTerm($id, $label);
    }

    $manager = $this->getMockBuilder(ContentSpaceManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getContentSpaces',
        'getValidContentSpaceIdsForCurrentUser',
        'getCurrentContentSpaceId',
        'setCurrentContentSpaceId',
        'isContentSpaceRestrictPath',
      ])
      ->getMock();
    $manager->method('getContentSpaces')->willReturn($terms);
    $manager->method('getValidContentSpaceIdsForCurrentUser')->willReturn($user_space_ids);
    $manager->method('getCurrentContentSpaceId')->willReturn($current_space_id);
    $manager->method('isContentSpaceRestrictPath')->willReturn($can_change);
    return $manager;
  }

  /**
   * Mocks a taxonomy term option for the selector.
   */
  private function mockContentSpaceTerm(int $id, string $label): TermInterface {
    $term = $this->createMock(TermInterface::class);
    $term->method('id')->willReturn($id);
    $term->method('label')->willReturn($label);
    return $term;
  }

  /**
   * Mocks the current path service.
   */
  private function mockCurrentPath(string $path): CurrentPathStack {
    $current_path = $this->createMock(CurrentPathStack::class);
    $current_path->method('getPath')->willReturn($path);
    return $current_path;
  }

}
