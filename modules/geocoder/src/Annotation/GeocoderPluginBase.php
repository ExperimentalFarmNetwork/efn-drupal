<?php

/**
 * @file
 * Contains \Drupal\geocoder\Annotation\GeocoderProvider.
 */

namespace Drupal\geocoder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a base class for geocoder plugin annotations.
 */
class GeocoderPluginBase extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the geocoder plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

}
