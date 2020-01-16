<?php

declare(strict_types = 1);

namespace Drupal\geocoder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Geocoder\Model\AddressCollection;

/**
 * Provides a geocoder factory class.
 */
class Geocoder implements GeocoderInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The geocoder provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * Constructs a geocoder factory class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The geocoder provider plugin manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ProviderPluginManager $provider_plugin_manager) {
    $this->config = $config_factory->get('geocoder.settings');
    $this->providerPluginManager = $provider_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(string $data, array $providers) {
    /** @var \Drupal\geocoder\GeocoderProviderInterface $provider */
    foreach ($providers as $provider) {
      try {
        $result = $provider->getPlugin()->geocode($data);
        if (!isset($result) || $result->isEmpty()) {
          throw new \Exception();
        }
        return $result;
      }
      catch (\Exception $e) {
        static::log($e->getMessage());
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function reverse(string $latitude, string $longitude, array $providers): ?AddressCollection {
    /** @var \Drupal\geocoder\GeocoderProviderInterface $provider */
    foreach ($providers as $provider) {
      try {
        $result = $provider->getPlugin()->reverse($latitude, $longitude);
        if (!isset($result) || $result->isEmpty()) {
          throw new \Exception();
        }
        return $result;
      }
      catch (\Exception $e) {
        static::log($e->getMessage());
      }
    }
    return NULL;
  }

  /**
   * Log a message in the Drupal watchdog and on screen.
   *
   * @param string $message
   *   The message.
   */
  public static function log($message) {
    \Drupal::logger('geocoder')->error($message);
  }

}
