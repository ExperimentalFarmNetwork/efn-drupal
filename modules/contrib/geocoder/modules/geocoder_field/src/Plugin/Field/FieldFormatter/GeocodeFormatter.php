<?php

namespace Drupal\geocoder_field\Plugin\Field\FieldFormatter;

use Drupal\geocoder_field\Plugin\Field\GeocodeFormatterBase;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_geocode_formatter",
 *   label = @Translation("Geocode"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   }
 * )
 */
class GeocodeFormatter extends GeocodeFormatterBase {

}
