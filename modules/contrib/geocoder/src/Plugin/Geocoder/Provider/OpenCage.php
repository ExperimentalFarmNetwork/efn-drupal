<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides an OpenCage geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "opencage",
 *   name = "OpenCage",
 *   handler = "\Geocoder\Provider\OpenCage",
 *   arguments = {
 *     "apikey" = NULL,
 *     "usessl" = FALSE,
 *     "locale" = NULL
 *   }
 * )
 */
class OpenCage extends ProviderUsingHandlerWithAdapterBase {}
