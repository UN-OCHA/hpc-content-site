<?php

namespace Drupal\gho_ncms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Publisher entity.
 *
 * @ConfigEntityType(
 *   id = "publisher",
 *   label = @Translation("Publisher"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\gho_ncms\PublisherListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gho_ncms\Form\PublisherForm",
 *       "edit" = "Drupal\gho_ncms\Form\PublisherForm",
 *       "delete" = "Drupal\gho_ncms\Form\PublisherDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\gho_ncms\PublisherHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "publisher",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "known_hosts",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/publisher/{publisher}",
 *     "add-form" = "/admin/structure/publisher/add",
 *     "edit-form" = "/admin/structure/publisher/{publisher}/edit",
 *     "delete-form" = "/admin/structure/publisher/{publisher}/delete",
 *     "collection" = "/admin/structure/publisher"
 *   }
 * )
 */
class Publisher extends ConfigEntityBase implements PublisherInterface {

  /**
   * The Publisher ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Publisher label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getKnownHosts() {
    return array_map(function ($host) {
      return trim($host);
    }, explode("\n", $this->get('known_hosts') ?? ''));
  }

  /**
   * {@inheritdoc}
   */
  public function isKnownHost($host) {
    $known_hosts = $this->getKnownHosts();
    return in_array($host, $known_hosts);
  }

}
