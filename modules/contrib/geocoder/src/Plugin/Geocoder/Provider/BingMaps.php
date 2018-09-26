<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a BingMaps geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "bingmaps",
 *   name = "BingMaps",
 *   handler = "\Geocoder\Provider\BingMaps",
 *   arguments = {
 *     "apikey" = NULL,
 *     "locale" = NULL
 *   }
 * )
 */
class BingMaps extends ProviderUsingHandlerWithAdapterBase {}
