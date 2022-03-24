<?php

namespace Drupal\gho_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Defines a schema extension for GHO.
 *
 * @SchemaExtension(
 *   id = "gho_schema_extension",
 *   name = "Schema extension for GHO",
 *   description = "A simple extension that adds node related fields.",
 *   schema = "gho_schema"
 * )
 */
class GhoSchemaExtension extends SdlSchemaExtensionPluginBase {

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
    $registry->addFieldResolver('Article', 'heroImage',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_hero_image'))
    );
    $registry->addFieldResolver('Article', 'thumbnail',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_thumbnail_image'))
    );
    $registry->addFieldResolver('Article', 'caption',
      $builder->fromParent()
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
        $builder->callback(function ($paragraphs) {
          $exclude_types = ['story'];
          $paragraphs = array_filter($paragraphs, function ($paragraph) use ($exclude_types) {
            return !in_array($paragraph->getParagraphType()->id(), $exclude_types) ? $paragraph : NULL;
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
    $registry->addFieldResolver('Caption', 'title',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_caption.first')),
    );
    $registry->addFieldResolver('Caption', 'body',
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
      $builder->callback(function ($paragraph) {
        return $paragraph->getParagraphType()->id;
      })
    );
    $registry->addFieldResolver('Paragraph', 'typeLabel',
      $builder->callback(function ($paragraph) {
        return $paragraph->getParagraphType()->label();
      })
    );
    $registry->addFieldResolver('Paragraph', 'rendered',
      $builder->produce('entity_rendered')
        ->map('entity', $builder->fromParent())
        ->map('mode', $builder->fromValue('default'))
    );
  }

}
