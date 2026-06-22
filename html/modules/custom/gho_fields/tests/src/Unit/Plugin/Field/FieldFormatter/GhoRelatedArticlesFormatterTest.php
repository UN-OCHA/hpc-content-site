<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Url;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoRelatedArticlesFormatter;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO related articles formatter helpers.
 */
#[Group('gho_fields')]
class GhoRelatedArticlesFormatterTest extends UnitTestCase {

  /**
   * Tests applicability is limited to menu link content references.
   */
  public function testIsApplicable(): void {
    $this->assertTrue(GhoRelatedArticlesFormatter::isApplicable($this->mockFieldDefinition('menu_link_content')));
    $this->assertFalse(GhoRelatedArticlesFormatter::isApplicable($this->mockFieldDefinition('node')));
  }

  /**
   * Tests related article render arrays are built from routed menu links.
   */
  public function testGetRelatedArticleList(): void {
    $node = $this->createMock(NodeInterface::class);
    $this->setNodeViewServices([
      3 => $node,
    ], [
      spl_object_id($node) => ['#theme' => 'node', '#view_mode' => 'related_article'],
    ]);

    $items = [
      $this->mockMenuTreeItem(Url::fromRoute('entity.node.canonical', ['node' => 3])),
      $this->mockMenuTreeItem(Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => 7])),
    ];

    $this->assertSame([
      ['#theme' => 'node', '#view_mode' => 'related_article'],
    ], GhoRelatedArticlesFormatter::getRelatedArticleList($items));
  }

  /**
   * Tests excluded nodes are not rendered.
   */
  public function testGetRelatedArticleListSkipsExcludedNodes(): void {
    $this->setNodeViewServices([3 => $this->createMock(NodeInterface::class)], []);

    $items = [
      $this->mockMenuTreeItem(Url::fromRoute('entity.node.canonical', ['node' => 3])),
    ];

    $this->assertSame([], GhoRelatedArticlesFormatter::getRelatedArticleList($items, [3]));
  }

  /**
   * Tests menu children are loaded and rendered as related articles.
   */
  public function testGetNodeListFromMenu(): void {
    $node = $this->createMock(NodeInterface::class);
    $root = (object) [
      'subtree' => [
        $this->mockMenuTreeItem(Url::fromRoute('entity.node.canonical', ['node' => 5])),
      ],
    ];
    $menu_tree = $this->createMock(MenuLinkTreeInterface::class);
    $menu_tree->method('load')->willReturn(['root' => $root]);
    $menu_tree->method('transform')->willReturnArgument(0);
    $this->setNodeViewServices([
      5 => $node,
    ], [
      spl_object_id($node) => ['#theme' => 'node', '#view_mode' => 'related_article'],
    ], $menu_tree);

    $this->assertSame([
      ['#theme' => 'node', '#view_mode' => 'related_article'],
    ], GhoRelatedArticlesFormatter::getNodeListFromMenu('main.menu_item'));
  }

  /**
   * Mocks field storage target type for applicability checks.
   */
  private function mockFieldDefinition(string $target_type): FieldDefinitionInterface {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getSetting')->with('target_type')->willReturn($target_type);

    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getFieldStorageDefinition')->willReturn($storage);
    return $definition;
  }

  /**
   * Registers the node storage and view builder used by the static helper.
   */
  private function setNodeViewServices(array $nodes, array $views, ?MenuLinkTreeInterface $menu_tree = NULL): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturnCallback(static fn (int $id) => $nodes[$id] ?? NULL);

    $view_builder = $this->createMock(EntityViewBuilderInterface::class);
    $view_builder->method('view')->willReturnCallback(static fn (NodeInterface $node, string $view_mode) => $views[spl_object_id($node)] ?? []);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);
    $entity_type_manager->method('getViewBuilder')->with('node')->willReturn($view_builder);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    if ($menu_tree !== NULL) {
      $container->set('menu.link_tree', $menu_tree);
    }
    \Drupal::setContainer($container);
  }

  /**
   * Mocks the small part of a menu tree item used by the formatter helper.
   */
  private function mockMenuTreeItem(Url $url): object {
    $link = $this->createMock(MenuLinkInterface::class);
    $link->method('getUrlObject')->willReturn($url);

    return (object) [
      'link' => $link,
    ];
  }

}
