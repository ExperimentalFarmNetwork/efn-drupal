<?php

/**
 * @file
 * Contains \Drupal\geofield\Annotation\GeofieldBackend.
 */

namespace Drupal\geofield\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GeofieldBackend annotation object.
 *
 * @ingroup geofield_api
 *
 * @Annotation
 */
class GeofieldBackend extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the geofield backend.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

}