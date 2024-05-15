<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\Taxonomy\ContentSpace;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Prophecy\Argument;

/**
 * Tests the ncms_ui.content_space.manager service.
 */
class ContentSpaceManagerTest extends UnitTestCase {

  /**
   * Data provider for testIsContentSpaceRestrictPath.
   */
  public function isContentSpaceRestrictPathDataProvider() {
    return [
      ['/admin/content', TRUE],
      ['/admin/media/grid', TRUE],
      ['/article/123', FALSE],
    ];
  }

  /**
   * Test the isContentSpaceRestrictPath method.
   *
   * @dataProvider isContentSpaceRestrictPathDataProvider
   */
  public function testIsContentSpaceRestrictPath($path, $result) {
    $content_space_manager = $this->getContentSpaceManager();
    $this->assertEquals($result, $content_space_manager->isContentSpaceRestrictPath($path));
  }

  /**
   * Test the shouldRestrictContentSpaces method.
   */
  public function testShouldRestrictContentSpaces() {
    $content_space_manager = $this->getContentSpaceManager();
    $this->assertEquals(TRUE, $content_space_manager->shouldRestrictContentSpaces('node'));
    $this->assertEquals(TRUE, $content_space_manager->shouldRestrictContentSpaces('media'));
    $content_space_manager = $this->getContentSpaceManager(1, ['administer nodes']);
    $this->assertEquals(FALSE, $content_space_manager->shouldRestrictContentSpaces('node'));
    $this->assertEquals(TRUE, $content_space_manager->shouldRestrictContentSpaces('media'));
    $content_space_manager = $this->getContentSpaceManager(1, ['administer media']);
    $this->assertEquals(TRUE, $content_space_manager->shouldRestrictContentSpaces('node'));
    $this->assertEquals(FALSE, $content_space_manager->shouldRestrictContentSpaces('media'));
  }

  /**
   * Test the userIsInValidContentSpace method.
   */
  public function testUserIsInValidContentSpace() {
    $content_spaces = [
      1 => $this->createContentSpace(1, 'Content space 1'),
      2 => $this->createContentSpace(2, 'Content space 2'),
    ];
    $current_content_space = 1;
    $user_content_spaces = [1, 2];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, $current_content_space, $user_content_spaces);
    $this->assertEquals(TRUE, $content_space_manager->userIsInValidContentSpace());
    $user_content_spaces = [2];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, $current_content_space, $user_content_spaces);
    $this->assertEquals(FALSE, $content_space_manager->userIsInValidContentSpace());
  }

  /**
   * Test the getValidContentSpaceIdsForUser method.
   */
  public function testGetValidContentSpaceIdsForCurrentUser() {
    $content_spaces = [
      1 => $this->createContentSpace(1, 'Content space 1'),
      2 => $this->createContentSpace(2, 'Content space 2'),
      3 => $this->createContentSpace(3, 'Content space 3'),
      4 => $this->createContentSpace(4, 'Content space 4'),
    ];
    $current_content_space = 1;
    $user_content_spaces = [1, 2];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, $current_content_space, $user_content_spaces);
    $content_space_ids = $content_space_manager->getValidContentSpaceIdsForCurrentUser();
    $this->assertIsArray($content_space_ids);
    $this->assertEquals([1 => 1, 2 => 2], $content_space_ids);
  }

  /**
   * Test the buildContentSpaceSelector method.
   */
  public function testBuildContentSpaceSelector() {
    $content_spaces = [
      1 => $this->createContentSpace(1, 'Content space 1'),
      2 => $this->createContentSpace(2, 'Content space 2'),
      3 => $this->createContentSpace(3, 'Content space 3'),
      4 => $this->createContentSpace(4, 'Content space 4'),
    ];
    $current_content_space = 1;
    $user_content_spaces = [1, 3];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, $current_content_space, $user_content_spaces);
    $selector_element = $content_space_manager->buildContentSpaceSelector();
    $this->assertIsArray($selector_element);
    $this->assertEquals(1, $selector_element['#default_value']);
    $options = $selector_element['#options'];
    $this->assertArrayHasKey('My content spaces', $options);
    $this->assertEquals([
      1 => 'Content space 1',
      3 => 'Content space 3',
    ], $options['My content spaces']);
    $this->assertArrayHasKey('Other content spaces', $options);
    $this->assertEquals([
      2 => 'Content space 2',
      4 => 'Content space 4',
    ], $options['Other content spaces']);
  }

  /**
   * Test the getCurrentContentSpace method.
   */
  public function testGetCurrentContentSpace() {
    $content_spaces = [
      1 => $this->createContentSpace(1, 'Content space 1'),
      2 => $this->createContentSpace(2, 'Content space 2'),
    ];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, 1);
    $this->assertEquals($content_spaces[1], $content_space_manager->getCurrentContentSpace());
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, NULL, [2]);
    $this->assertEquals($content_spaces[2], $content_space_manager->getCurrentContentSpace());
  }

  /**
   * Test the getCurrentContentSpaceId method.
   */
  public function testGetCurrentContentSpaceId() {
    $content_spaces = [
      1 => $this->createContentSpace(1, 'Content space 1'),
      2 => $this->createContentSpace(2, 'Content space 2'),
    ];
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, 1);
    $this->assertEquals(1, $content_space_manager->getCurrentContentSpaceId());
    $content_space_manager = $this->getContentSpaceManager(1, [], $content_spaces, NULL, [2]);
    $this->assertEquals(2, $content_space_manager->getCurrentContentSpaceId());
  }

  /**
   * Get the content space manager.
   *
   * @return Drupal\ncms_ui\ContentSpaceManager
   *   The content space manager service.
   */
  private function getContentSpaceManager($uid = 1, array $user_permissions = NULL, array $content_spaces = NULL, $current_content_space_id = NULL, array $user_content_space_ids = NULL) {
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($uid);
    $user = $this->prophesize(UserInterface::class);
    if ($user_permissions) {
      $user->hasPermission(Argument::any())->willReturn(FALSE);
      foreach ($user_permissions as $permission) {
        $user->hasPermission($permission)->willReturn(TRUE);
      }
    }
    if ($user_content_space_ids) {
      $content_space_field = $this->prophesize(EntityReferenceFieldItemListInterface::class);
      $content_space_field->getValue()->willReturn(array_map(function ($id) {
        return ['target_id' => $id];
      }, $user_content_space_ids));
      $user->get('field_content_spaces')->willReturn($content_space_field);
    }
    $user_storage = $this->prophesize(UserStorageInterface::class);
    $user_storage->load($uid)->willReturn($user);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage);
    if ($content_spaces) {
      $term_storage = $this->prophesize(TermStorageInterface::class);
      $term_storage->loadByProperties(['vid' => 'content_space'])->willReturn($content_spaces);
      $term_storage->load(Argument::any())->willReturn(NULL);
      foreach ($content_spaces as $id => $content_space) {
        $term_storage->load($id)->willReturn($content_space);
      }
      $entity_type_manager->getStorage('taxonomy_term')->willReturn($term_storage);
    }
    $temp_store_factory = $this->prophesize(PrivateTempStoreFactory::class);
    $private_temp_store = $this->prophesize(PrivateTempStore::class);
    $private_temp_store->get('content_space')->willReturn($current_content_space_id);
    $temp_store_factory->get('ncms_content_manager')->willReturn($private_temp_store);
    $views_join = $this->prophesize(ViewsHandlerManager::class);

    $content_space_manager = new ContentSpaceManager($entity_type_manager->reveal(), $account->reveal(), $temp_store_factory->reveal(), $views_join->reveal());
    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });
    $content_space_manager->setStringTranslation($string_translation->reveal());
    return $content_space_manager;
  }

  /**
   * Create a content space term.
   *
   * @return \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace
   *   A content space term.
   */
  private function createContentSpace($id, $label) {
    $term = $this->prophesize(ContentSpace::class);
    $term->id()->willReturn($id);
    $term->label()->willReturn($label);
    return $term->reveal();
  }

}
