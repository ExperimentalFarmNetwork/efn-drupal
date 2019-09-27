<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Yandex geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "yandex",
 *   name = "Yandex",
 *   handler = "\Geocoder\Provider\Yandex\Yandex",
 *   arguments = {
 *     "toponym"
 *   }
 * )
 */
class Yandex extends ConfigurableProviderUsingHandlerWithAdapterBase {}
