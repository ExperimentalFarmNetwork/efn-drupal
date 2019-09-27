<?php

namespace Drupal\profile\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the profile entity type.
 *
 * @deprecated in Profile 8.x-1.0.
 *
 * @EntityReferenceSelection(
 *   id = "default:profile",
 *   label = @Translation("Profile selection"),
 *   entity_types = {"profile"},
 *   group = "default",
 *   weight = 1
 * )
 */
class ProfileSelection extends DefaultSelection {}
