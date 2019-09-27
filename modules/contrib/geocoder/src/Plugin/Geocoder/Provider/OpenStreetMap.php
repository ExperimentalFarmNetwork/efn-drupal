<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides an OpenStreetMap geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "openstreetmap",
 *   name = "OpenStreetMap",
 *   handler = "\Geocoder\Provider\Nominatim\Nominatim",
 *   arguments = {
 *     "rootUrl" = "https://nominatim.openstreetmap.org",
 *     "userAgent" = "",
 *     "referer" = ""
 *   }
 * )
 */
class OpenStreetMap extends ConfigurableProviderUsingHandlerWithAdapterBase {}
