<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a MapQuest geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "mapquest",
 *   name = "MapQuest",
 *   handler = "\Geocoder\Provider\MapQuest",
 *   arguments = {
 *     "apikey" = NULL,
 *     "licensed" = FALSE
 *   }
 * )
 */
class MapQuest extends ProviderUsingHandlerWithAdapterBase {}
