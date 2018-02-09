<?php

namespace Drupal\geocoder;

/**
 * Provides a geocoder factory method interface.
 */
interface GeocoderInterface {

  /**
   * Geocodes a string.
   *
   * @param string $data
   *   The string to geocoded.
   * @param string[] $plugins
   *   A list of plugin identifiers to use.
   * @param array $options
   *   (optional) An associative array with plugin options, keyed plugin by the
   *   plugin id. Defaults to an empty array.
   *
   * @return \Geocoder\Model\AddressCollection|null
   *   An address collection or NULL on gecoding failure.
   */
  public function geocode($data, array $plugins, array $options = []);

  /**
   * Reverse geocode coordinates.
   *
   * @param string $latitude
   *   The latitude.
   * @param string $longitude
   *   The longitude.
   * @param string[] $plugins
   *   A list of plugin identifiers to use.
   * @param array $options
   *   (optional) An associative array with plugin options, keyed plugin by the
   *   plugin id. Defaults to an empty array.
   *
   * @return \Geocoder\Model\AddressCollection|null
   *   An address collection or NULL on gecoding failure.
   */
  public function reverse($latitude, $longitude, array $plugins, array $options = []);

}
