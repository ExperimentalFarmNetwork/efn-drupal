<?php

namespace Drupal\geocoder_address\Plugin\Geocoder\Preprocessor;

use Drupal\geocoder_field\PreprocessorBase;

/**
 * Provides a geocoder preprocessor plugin for address fields.
 *
 * @GeocoderPreprocessor(
 *   id = "address",
 *   name = "Address",
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class Address extends PreprocessorBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess() {
    parent::preprocess();

    $defaults = [
      'address_line1' => NULL,
      'locality' => NULL,
      'dependent_locality' => NULL,
      'administrative_area' => NULL,
      'postal_code' => NULL,
      'country_code' => NULL,
    ];

    foreach ($this->field->getValue() as $delta => $value) {
      $value += $defaults;
      $address = [
        $value['address_line1'],
        $value['locality'],
        $value['dependent_locality'],
        str_replace($value['country_code'] . '-', '', $value['administrative_area']),
        $value['postal_code'],
        $value['country_code'],
      ];

      $value['value'] = implode(',', array_filter($address));
      $this->field->set($delta, $value);
    }

    return $this;
  }

}
