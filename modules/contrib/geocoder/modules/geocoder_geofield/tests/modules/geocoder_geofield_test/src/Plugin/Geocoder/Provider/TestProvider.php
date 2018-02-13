<?php

namespace Drupal\geocoder_geofield_test\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderBase;
use Geocoder\Model\AddressFactory;

/**
 * Provides a geocoding provider for testing purposes.
 *
 * @GeocoderProvider(
 *  id = "test_provider",
 *  name = "Test provider"
 * )
 */
class TestProvider extends ProviderBase {

  /**
   * The address factory.
   *
   * @var \Geocoder\Model\AddressFactory
   */
  protected $addressFactory;

  /**
   * {@inheritdoc}
   */
  protected function doGeocode($source) {
    switch ($source) {
      case 'Gotham City':
        return $this->getAddressFactory()->createFromArray([['latitude' => 20, 'longitude' => 40]]);

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doReverse($latitude, $longitude) {
    return FALSE;
  }

  /**
   * Returns the address factory.
   *
   * @return \Geocoder\Model\AddressFactory
   *   Returns Address factory.
   */
  protected function getAddressFactory() {
    if (!isset($this->addressFactory)) {
      $this->addressFactory = new AddressFactory();
    }
    return $this->addressFactory;
  }

}
