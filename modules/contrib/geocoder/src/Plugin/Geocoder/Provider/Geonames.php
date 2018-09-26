<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Geoip geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "geonames",
 *   name = "Geonames",
 *   handler = "\Geocoder\Provider\Geonames",
 *   arguments = {
 *     "username" = NULL,
 *     "locale" = NULL
 *   }
 * )
 */
class Geonames extends ProviderUsingHandlerWithAdapterBase {}
