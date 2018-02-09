<?php

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
 *     "locale" = NULL,
 *     "region" = NULL,
 *     "usessl" = FALSE,
 *     "apikey" = NULL,
 *   }
 * )
 */
class GoogleMaps extends ProviderUsingHandlerWithAdapterBase {}
