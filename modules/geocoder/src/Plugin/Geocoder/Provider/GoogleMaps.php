<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Provider\GoogleMaps.
 */

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a GoogleMaps geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "googlemaps",
 *   name = "GoogleMaps",
 *   handler = "\Geocoder\Provider\GoogleMaps",
 *   arguments = {
 *     "locale",
 *     "region",
 *     "useSsl" = FALSE,
 *     "apiKey"
 *   }
 * )
 */
class GoogleMaps extends ProviderUsingHandlerWithAdapterBase {}
