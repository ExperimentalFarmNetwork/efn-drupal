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
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class Text extends DataPrepareBase implements GeocoderPluginInterface {

  /**
   * Get the prepared reverse geocode values.
   *
   * @inheritDoc
   */
  public function getPreparedReverseGeocodeValues(array $values = []) {
    return array_map(function ($value) {
      return array_combine(['lat', 'lon'], array_map(
        'trim',
        explode(',', trim($value['value']), 2)
      ));
    }, $values);
  }

}
