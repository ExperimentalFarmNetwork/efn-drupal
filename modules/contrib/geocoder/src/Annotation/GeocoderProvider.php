<?php

namespace Drupal\geocoder\Annotation;

/**
 * Defines a geocoder provider plugin annotation object.
 *
 * @Annotation
 */
class GeocoderProvider extends GeocoderPluginBase {

  /**
   * The plugin handler.
   *
   * This is the fully qualified class name of the plugin handler.
   *
   * @var string
   */
  public $handler = NULL;

  /**
   * Handler arguments names.
   *
   * Plugin annotations can define each item in the array either as key-value
   * pair or as simple array item. When the argument name is in the key, the
   * value will contain the default value to be used if the plugin instance
   * didn't provide a value.
   *
   * @var array
   */
  public $arguments = [];

}
