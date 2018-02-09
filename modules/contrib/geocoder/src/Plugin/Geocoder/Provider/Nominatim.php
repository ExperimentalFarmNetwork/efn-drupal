<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Nominatim geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "nominatim",
 *   name = "Nominatim",
 *   handler = "\Geocoder\Provider\Nominatim",
 *   arguments = {
 *     "rooturl" = NULL,
 *     "locale" = NULL
 *   }
 * )
 */
class Nominatim extends ProviderUsingHandlerWithAdapterBase {}
