<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the force update timestamp of an entity.
 *
 * @DataProducer(
 *   id = "entity_force_update",
 *   name = @Translation("Entity force update"),
 *   description = @Translation("Returns the timestamp that this entity was last forced to udpate."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("Force update timestamp")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     )
 *   }
 * )
 */
class EntityForceUpdate extends DataProducerPluginBase {

  /**
   * Resolver.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   *
   * @return int|null
   *   The timestamp of the last time an entity has been forced to update, or
   *   NULL.
   */
  public function resolve(EntityInterface $entity) {
    return $entity->get('force_update')?->value;
  }

}
