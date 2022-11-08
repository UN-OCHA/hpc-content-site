<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a schema extension for the HPC Content Module schema.
 *
 * @SchemaExtension(
 *   id = "ncms_schema_extension",
 *   name = "Schema extension for the HPC Content Module schema",
 *   description = "A simple extension that adds node related fields.",
 *   schema = "ncms_schema"
 * )
 */
class NcmsSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $this->addQueries($registry, $builder);

    $this->addFieldResolverArticle($registry, $builder);
    $this->addFieldResolverHeroImage($registry, $builder);
    $this->addFieldResolverThumbnail($registry, $builder);
    $this->addFieldResolverCaption($registry, $builder);
    $this->addFieldResolverAuthor($registry, $builder);
    $this->addFieldResolverContentSpace($registry, $builder);
    $this->addFieldResolverParagraph($registry, $builder);
  }

  /**
   * Add field resolvers for queries.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addQueries(ResolverRegistryInterface $registry, ResolverBuilder $builder) {

    $registry->addFieldResolver('Query', 'connection',
      $builder->produce('connection_status'),
    );

    $registry->addFieldResolver('Query', 'articleExport',
      $builder->produce('article_export')
        ->map('tags', $builder->fromArgument('tags'))
    );

    $registry->addFieldResolver('Query', 'articleSearch',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_search_by_title')
          ->map('type', $builder->fromValue('node'))
          ->map('bundles', $builder->fromValue(['article']))
          ->map('title', $builder->fromArgument('title'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view'))
      ),
    );

    $registry->addFieldResolver('Query', 'article',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('node'))
          ->map('bundles', $builder->fromValue(['article']))
          ->map('id', $builder->fromArgument('id'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view'))
      ),
    );

    $registry->addFieldResolver('Query', 'articleTranslations',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('node'))
          ->map('bundles', $builder->fromValue(['article']))
          ->map('id', $builder->fromArgument('id'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view')),
        $builder->produce('entity_translations')
          ->map('entity', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Query', 'paragraph',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('paragraph'))
          ->map('id', $builder->fromArgument('id'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view'))
      ),
    );
  }

  /**
   * Add field resolvers for articles.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverArticle(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Article', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'section',
      $builder->compose(
        $builder->produce('entity_reference_single')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_section')),
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent())
      )
    );
    $registry->addFieldResolver('Article', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_summary.value')),
    );
    $registry->addFieldResolver('Article', 'status',
      $builder->produce('entity_published')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Article', 'content_space',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_content_space')),
    );
    $registry->addFieldResolver('Article', 'tags',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_tags')),
        $builder->callback(function ($tags) {
          $tags = array_map(function ($tag) {
            return $tag->label();
          }, $tags);
          return $tags;
        }),
      ),
    );
    $registry->addFieldResolver('Article', 'language',
      $builder->compose(
        $builder->produce('entity_language')
          ->map('entity', $builder->fromParent()),
        $builder->callback(function ($language) {
          return (object) [
            'id' => $language->getId(),
            'name' => $language->getName(),
          ];
        })
      )
    );
    $registry->addFieldResolver('Article', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Article', 'updated',
      $builder->produce('entity_changed')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Article', 'image',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_hero_image'))
    );
    $registry->addFieldResolver('Article', 'imageCaption',
      $builder->fromParent()
    );
    $registry->addFieldResolver('Article', 'thumbnail',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_thumbnail_image'))
    );
    $registry->addFieldResolver('Article', 'author',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_author'))
    );
    $registry->addFieldResolver('Article', 'content',
      $builder->compose(
        $builder->produce('entity_reference_revisions')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_paragraphs')),
        $builder->callback(function (array $paragraphs) {
          $exclude_types = [];
          $paragraphs = array_filter($paragraphs, function ($paragraph) use ($exclude_types) {
            if (!empty($exclude_types) && in_array($paragraph->getParagraphType()->id(), $exclude_types)) {
              return NULL;
            }
            if ($paragraph->getParagraphType()->id() == 'story') {
              // Check if the referenced item is published.
              $story = $this->entityTypeManager->getStorage('node')->load($paragraph->get('field_story')->target_id);
              return $story && $story instanceof NodeInterface && $story->isPublished();
            }
            return $paragraph;
          });

          return $paragraphs;
        }),
      )
    );
  }

  /**
   * Add field resolvers for hero images.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverHeroImage(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('HeroImage', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('HeroImage', 'credits',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_credits.value')),
    );
    $registry->addFieldResolver('HeroImage', 'image',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:file'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_media_image.entity')),
        $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('full_width_2_1_50'))
      )
    );
    $registry->addFieldResolver('HeroImage', 'imageUrl',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('thumbnail.entity')),
        $builder->produce('image_url')
          ->map('entity', $builder->fromParent())
      )
    );
  }

  /**
   * Add field resolvers for hero images.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverThumbnail(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Thumbnail', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Thumbnail', 'image',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('thumbnail.entity')),
        $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('media_library'))
      )
    );
    $registry->addFieldResolver('Thumbnail', 'imageUrl',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('thumbnail.entity')),
        $builder->produce('image_url')
          ->map('entity', $builder->fromParent())
      )
    );
  }

  /**
   * Add field resolvers for captions.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverCaption(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Caption', 'location',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_caption.first')),
    );
    $registry->addFieldResolver('Caption', 'text',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_caption.second')),
    );
  }

  /**
   * Add field resolvers for authors.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverAuthor(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Author', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Author', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Author', 'image',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:file'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_media_image.entity')),
        $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('author_x1'))
      )
    );
  }

  /**
   * Add field resolvers for content space.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverContentSpace(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('ContentSpace', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('ContentSpace', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('ContentSpace', 'tags',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_major_tags')),
        $builder->callback(function ($tags) {
          $tags = array_map(function ($tag) {
            return $tag->label();
          }, $tags);
          return $tags;
        }),
      ),
    );
  }

  /**
   * Add field resolvers for paragraphs.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverParagraph(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Paragraph', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Paragraph', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Paragraph', 'type',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getParagraphType()->id;
      })
    );
    $registry->addFieldResolver('Paragraph', 'typeLabel',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getParagraphType()->label();
      })
    );
    $registry->addFieldResolver('Paragraph', 'promoted',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getBehaviorSetting('promoted_behavior', 'promoted', FALSE);
      })
    );
    $registry->addFieldResolver('Paragraph', 'rendered',
      $builder->produce('entity_rendered')
        ->map('entity', $builder->fromParent())
        ->map('mode', $builder->fromValue('default'))
    );
    $registry->addFieldResolver('Paragraph', 'configuration',
      $builder->produce('entity_configuration')
        ->map('entity', $builder->fromParent())
    );

  }

}
