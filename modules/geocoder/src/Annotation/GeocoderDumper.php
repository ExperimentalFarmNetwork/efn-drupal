<?php

/**
 * @file
 * Contains \Drupal\geocoder\Annotation\DumperProvider.
 */

namespace Drupal\geocoder\Annotation;

/**
 * Defines a geocoder dumper plugin annotation object.
 *
 * @Annotation
 */
class GeocoderDumper extends GeocoderPluginBase {

  /**
   * The plugin handler.
   *
   * This is the fully qualified class name of the plugin handler.
   *
   * @var string (optional)
   */
  public $handler = NULL;

}
