<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a FreeGeoIp geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "freegeoip",
 *   name = "FreeGeoIp",
 *   handler = "\Geocoder\Provider\FreeGeoIp"
 * )
 */
class FreeGeoIp extends ProviderUsingHandlerWithAdapterBase {}
