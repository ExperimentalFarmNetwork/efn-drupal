<?php

namespace Drupal\geocoder\Plugin\Geocoder\DataPrepare;

use Drupal\geocoder\Plugin\Geocoder\DataPrepareBase;
use Drupal\geocoder\Plugin\GeocoderPluginInterface;

/**
 * Class Text.
 *
 * @GeocoderPlugin(
 *  id = "data_prepare_string",
 *  name = "Text",
 *  field_types = {
 *     "string"
 *   }
 * )
 */
class Text extends DataPrepareBase implements GeocoderPluginInterface {

  /**
   * Get the prepared reverse geocode values.
   *
   * @inheritDoc
   */
  public function getPreparedReverseGeocodeValues(array $values = array()) {
    foreach ($values as $index => $value) {
      list($lat, $lon) = explode(',', trim($value['value']));
      $values[$index] += array(
        'lat' => trim($lat),
        'lon' => trim($lon),
      );
    }

    return $values;
  }

}
