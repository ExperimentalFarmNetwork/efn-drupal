<?php

namespace Drupal\geocoder\Plugin\Geocoder\Formatter;

use Geocoder\Model\Address;

/**
 * Provides an interface for geocoder formatter plugins.
 *
 * Dumpers are plugins that knows to format geographical data into an industry
 * standard format.
 */
interface FormatterInterface {

  /**
   * Dumps the argument into a specific format.
   *
   * @param \Geocoder\Model\Address $address
   *   The address to be formatted.
   *
   * @return string
   *   The formatted address.
   */
  public function format(Address $address);

}
