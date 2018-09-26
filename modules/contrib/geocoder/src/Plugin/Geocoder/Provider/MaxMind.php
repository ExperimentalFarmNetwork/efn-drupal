<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a MaxMind geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "maxmind",
 *   name = "MaxMind",
 *   handler = "\Geocoder\Provider\MaxMind",
 *   arguments = {
 *     "apikey" = NULL,
 *     "service" = "f",
 *     "usessl" = FALSE
 *   }
 * )
 */
class MaxMind extends ProviderUsingHandlerWithAdapterBase {}
