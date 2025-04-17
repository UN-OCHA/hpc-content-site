<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\ncms_graphql\ResultWrapperInterface;
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

    $this->addFieldResolverDocument($registry, $builder);
    $this->addFieldResolverArticle($registry, $builder);
    $this->addFieldResolverHeroImage($registry, $builder);
    $this->addFieldResolverThumbnail($registry, $builder);
    $this->addFieldResolverCaption($registry, $builder);
    $this->addFieldResolverAuthor($registry, $builder);
    $this->addFieldResolverContentSpace($registry, $builder);
    $this->addFieldResolverParagraph($registry, $builder);
    $this->addFieldResolverDocumentChapter($registry, $builder);
    $this->addFieldResolverTag($registry, $builder);

    $this->addListFieldResolvers('ArticleList', $registry, $builder);
    $this->addListFieldResolvers('DocumentList', $registry, $builder);
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

    // Documents.
    $registry->addFieldResolver('Query', 'documentExport',
      $builder->produce('document_export')
        ->map('tags', $builder->fromArgument('tags'))
    );
    $registry->addFieldResolver('Query', 'documentSearch',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_search_by_title')
          ->map('type', $builder->fromValue('node'))
          ->map('bundles', $builder->fromValue(['document']))
          ->map('title', $builder->fromArgument('title'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view'))
      ),
    );
    $registry->addFieldResolver('Query', 'document',
      $builder->compose(
        $builder->produce('hid_user'),
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('node'))
          ->map('bundles', $builder->fromValue(['document']))
          ->map('id', $builder->fromArgument('id'))
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view'))
      ),
    );

    // Articles.
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

    // Paragraph.
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

    // Tag.
    $registry->addFieldResolver('Query', 'tag',
      $builder->compose(
        $builder->fromArgument('name'),
        $builder->callback(function ($name) {
          $conditions = [
            'vid' => ['document_type', 'year', 'theme', 'country'],
            'name' => [$name],
          ];
          return array_map(function ($key, $value) {
            return ['field' => $key, 'value' => $value, 'operator' => 'IN'];
          }, array_keys($conditions), $conditions);
        }),
        $builder->produce('entity_query')
          ->map('type', $builder->fromValue('taxonomy_term'))
          ->map('allowed_filters', $builder->fromValue(['vid', 'name']))
          ->map('conditions', $builder->fromParent())
          ->map('access_user', $builder->fromParent())
          ->map('access_operation', $builder->fromValue('view')),
        $builder->callback(function ($ids) {
          return count($ids) == 1 ? reset($ids) : NULL;
        }),
        $builder->produce('entity_load', [
          'type' => $builder->fromValue('taxonomy_term'),
          'id' => $builder->fromParent(),
        ]),
      ),
    );
  }

  /**
   * Add field resolvers for documents.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverDocument(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Document', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Document', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Document', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Document', 'title_short',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_short_title.value')),
    );

    $registry->addFieldResolver('Document', 'status',
      $builder->produce('entity_published')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Document', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Document', 'updated',
      $builder->produce('entity_changed')
        ->map('entity', $builder->fromParent()),
    );
    $registry->addFieldResolver('Document', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_summary.value')),
    );
    $registry->addFieldResolver('Document', 'image',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_hero_image'))
    );
    $registry->addFieldResolver('Document', 'imageCaption',
      $builder->fromParent()
    );
    $registry->addFieldResolver('Document', 'content_space',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_content_space')),
    );
    $registry->addFieldResolver('Document', 'tags',
      $this->buildFromComputedTags($builder, 'node'),
    );
    $registry->addFieldResolver('Document', 'language',
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
    $registry->addFieldResolver('Document', 'chapters',
      $builder->compose(
        $builder->produce('entity_reference_revisions')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_paragraphs')),
        $builder->callback(function (array $paragraphs) {
          $include_types = ['document_chapter'];
          $paragraphs = array_filter($paragraphs, function ($paragraph) use ($include_types) {
            if (!in_array($paragraph->getParagraphType()->id(), $include_types)) {
              return NULL;
            }
            return $paragraph;
          });

          return $paragraphs;
        }),
      )
    );
    $registry->addFieldResolver('Document', 'autoVisible',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_automatically_visible.value')),
    );

    $registry->addFieldResolver('Document', 'forceUpdate',
      $builder->produce('entity_force_update')
        ->map('entity', $builder->fromParent()),
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
    $registry->addFieldResolver('Article', 'title_short',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_short_title.value')),
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
    $registry->addFieldResolver('Article', 'status',
      $builder->produce('entity_published')
        ->map('entity', $builder->fromParent()),
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
    $registry->addFieldResolver('Article', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_summary.value')),
    );
    $registry->addFieldResolver('Article', 'author',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_author'))
    );
    $registry->addFieldResolver('Article', 'content_space',
      $builder->produce('entity_reference_single')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_content_space')),
    );
    $registry->addFieldResolver('Article', 'content',
      $builder->compose(
        $builder->produce('entity_reference_revisions')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_paragraphs')),
        $builder->callback(function (array $paragraphs) {
          $exclude_types = [];
          /** @var \Drupal\paragraphs\Entity\Paragraph[] $paragraphs */
          $paragraphs = array_filter($paragraphs, function ($paragraph) use ($exclude_types) {
            /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
            if (!empty($exclude_types) && in_array($paragraph->getParagraphType()->id(), $exclude_types)) {
              return NULL;
            }
            // Exclude paragraphs that are nested inside another paragraph
            // using the layout paragraphs behavior.
            $layout_paragraph_parent = $paragraph->getBehaviorSetting('layout_paragraphs', 'parent_uuid');
            if ($layout_paragraph_parent) {
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
    $registry->addFieldResolver('Article', 'tags',
      $this->buildFromComputedTags($builder, 'node'),
    );
    $registry->addFieldResolver('Article', 'autoVisible',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_automatically_visible.value')),
    );

    $registry->addFieldResolver('Article', 'forceUpdate',
      $builder->produce('entity_force_update')
        ->map('entity', $builder->fromParent()),
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
      $this->buildFromComputedTags($builder, 'taxonomy_term'),
    );
  }

  /**
   * Add field resolvers for paragraphs.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   * @param string $parent
   *   An identifier for the parent field.
   */
  private function addFieldResolverParagraph(ResolverRegistryInterface $registry, ResolverBuilder $builder, $parent = 'Paragraph') {
    $registry->addFieldResolver($parent, 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($parent, 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($parent, 'type',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getParagraphType()->id;
      })
    );
    $registry->addFieldResolver($parent, 'typeLabel',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getParagraphType()->label();
      })
    );
    $registry->addFieldResolver($parent, 'promoted',
      $builder->callback(function (Paragraph $paragraph) {
        return $paragraph->getBehaviorSetting('promoted_behavior', 'promoted', FALSE);
      })
    );
    $registry->addFieldResolver($parent, 'rendered',
      $builder->produce('entity_rendered')
        ->map('entity', $builder->fromParent())
        ->map('mode', $builder->fromValue('default'))
    );
    $registry->addFieldResolver($parent, 'configuration',
      $builder->produce('entity_configuration')
        ->map('entity', $builder->fromParent())
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
  private function addFieldResolverDocumentChapter(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('DocumentChapter', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('DocumentChapter', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('DocumentChapter', 'title',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:paragraph'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_title.value')),
    );
    $registry->addFieldResolver('DocumentChapter', 'title_short',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:paragraph'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_short_title.value')),
    );
    $registry->addFieldResolver('DocumentChapter', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:paragraph'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_summary.value')),
    );
    $registry->addFieldResolver('DocumentChapter', 'hidden',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_hide_from_navigation.value')),
    );
    $registry->addFieldResolver('DocumentChapter', 'articles',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_articles')),
    );
    $registry->addFieldResolver('DocumentChapter', 'tags',
      $this->buildFromComputedTags($builder, 'paragraph'),
    );
  }

  /**
   * Add field resolvers for tags.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  private function addFieldResolverTag(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Tag', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Tag', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Tag', 'type',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

  }

  /**
   * Build the tags based on the comuted tags field of an entity.
   *
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\Composite
   *   The resolver chain that retrieves the value.
   */
  private function buildFromComputedTags(ResolverBuilder $builder, $entity_type_id) {
    return $builder->compose(
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:' . $entity_type_id))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_computed_tags.value')),
      $builder->callback(function ($value) {
        if (empty($value)) {
          return [];
        }
        $tags = explode(',', $value);
        return !empty($tags) ? $tags : [];
      }),
    );
  }

  /**
   * Add field resolvers for list types using a query connection.
   *
   * @param string $type
   *   The data type.
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addListFieldResolvers($type, ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver($type, 'count',
      $builder->callback(function (ResultWrapperInterface $wrapper) {
        return $wrapper->count();
      })
    );
    $registry->addFieldResolver($type, 'ids',
      $builder->callback(function (ResultWrapperInterface $wrapper) {
        return $wrapper->ids();
      })
    );
    $registry->addFieldResolver($type, 'metaData',
      $builder->callback(function (ResultWrapperInterface $wrapper) {
        return $wrapper->metaData();
      })
    );
    $registry->addFieldResolver($type, 'items',
      $builder->callback(function (ResultWrapperInterface $wrapper) {
        return $wrapper->items();
      })
    );
  }

}
