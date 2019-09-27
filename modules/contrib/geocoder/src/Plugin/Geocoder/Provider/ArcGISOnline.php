<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides an ArcGISOnline geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "arcgisonline",
 *   name = "ArcGISOnline",
 *   handler = "\Geocoder\Provider\ArcGISOnline\ArcGISOnline",
 *   arguments = {
 *     "sourceCountry" = ""
 *   }
 * )
 */
class ArcGisOnline extends ConfigurableProviderUsingHandlerWithAdapterBase {}
