<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a GoogleMaps geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "googlemaps_business",
 *   name = "GoogleMapsBusiness",
 *   handler = "\Geocoder\Provider\GoogleMapsBusiness",
 *   arguments = {
 *     "clientId" = NULL,
 *     "privateKey" = NULL,
 *     "locale" = NULL,
 *     "region" = NULL,
 *     "usessl" = FALSE
 *   }
 * )
 */
class GoogleMapsBusiness extends ProviderUsingHandlerWithAdapterBase {}
