<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerBase;

/**
 * Provides a Geoip geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "geoip",
 *   name = "Geoip",
 *   handler = "\Geocoder\Provider\Geoip"
 * )
 */
class Geoip extends ProviderUsingHandlerBase {}
