<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Provider\TomTom.
 */

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a TomTom geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "tomtom",
 *   name = "TomTom",
 *   handler = "\Geocoder\Provider\TomTom",
 *   arguments = {
 *     "apiKey"
 *   }
 * )
 */
class TomTom extends ProviderUsingHandlerWithAdapterBase {}
