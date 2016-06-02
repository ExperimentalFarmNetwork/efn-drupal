<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Provider\MaxMind.
 */

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
 *     "apiKey",
 *     "service" = "f",
 *     "useSsl" = FALSE
 *   }
 * )
 */
class MaxMind extends ProviderUsingHandlerWithAdapterBase {}
