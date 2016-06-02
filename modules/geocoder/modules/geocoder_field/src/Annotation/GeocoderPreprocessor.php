<?php

/**
 * @file
 * Contains \Drupal\geocoder_field\Annotation\GeocoderPreprocessor.
 */

namespace Drupal\geocoder_field\Annotation;

use Drupal\geocoder\Annotation\GeocoderPluginBase;

/**
 * Defines a geocoder preprocessor plugin annotation object.
 *
 * @Annotation
 */
class GeocoderPreprocessor extends GeocoderPluginBase {

  /**
   * The field types where this plugin applies.
   *
   * @var array
   */
  public $field_types;

  /**
   * The weight of this preprocessor.
   *
   * Many preprocessors are called to pre-process the same field. This value
   * can determine an order in which the preprocessors are called.
   *
   * @var int (optional)
   */
  public $weight = 0;

}
