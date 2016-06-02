<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Provider\BingMaps.
 */

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
 *     "apiKey"
 *   }
 * )
 */
class BingMaps extends ProviderUsingHandlerWithAdapterBase {}
