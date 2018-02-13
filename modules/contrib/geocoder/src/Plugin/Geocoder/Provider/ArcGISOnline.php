<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides an ArcGISOnline geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "arcgisonline",
 *   name = "ArcGISOnline",
 *   handler = "\Geocoder\Provider\ArcGISOnline",
 *   arguments = {
 *     "sourcecountry" = NULL,
 *     "usessl" = false
 *   }
 * )
 */
class ArcGisOnline extends ProviderUsingHandlerWithAdapterBase {}
