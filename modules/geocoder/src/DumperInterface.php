<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\DumperInterface.
 */

namespace Drupal\geocoder;

use Geocoder\Model\Address;

/**
 * Provides an interface for geocoder dumper plugins.
 *
 * Dumpers are plugins that knows to format geographical data into an industry
 * standard format.
 */
interface DumperInterface {

  /**
   * Dumps the argument into a specific format.
   *
   * @param \Geocoder\Model\Address
   *   The address to be formatted.
   *
   * @return string
   *   The formatted address.
   */
  public function dump(Address $address);

}
