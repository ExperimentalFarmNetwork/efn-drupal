<?php

namespace Drupal\geocoder_address\Plugin\Geocoder\Preprocessor;

use Drupal\geocoder_field\PreprocessorBase;
use Drupal\Core\Locale\CountryManager;

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
   * Decode country code into country name (if valid code).
   *
   * @param string $country_code
   *   The country code.
   *
   * @return string
   *   The country name or country code if not decode existing.
   */
  protected function countryCodeToString($country_code) {
    $countries = CountryManager::getStandardList();
    if (array_key_exists($country_code, $countries)) {
      return $countries[$country_code];
    }
    return $country_code;
  }

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
        $this->countryCodeToString($value['country_code']),
      ];

      $value['value'] = implode(',', array_filter($address));
      $this->field->set($delta, $value);
    }

    return $this;
  }

}
