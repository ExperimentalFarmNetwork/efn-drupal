<?php

namespace Drupal\geocoder;

use Geocoder\Exception\InvalidCredentials;

/**
 * Provides a geocoder factory class.
 */
class Geocoder implements GeocoderInterface {

  /**
   * The geocoder provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * Constructs a geocoder factory class.
   *
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The geocoder provider plugin manager service.
   */
  public function __construct(ProviderPluginManager $provider_plugin_manager) {
    $this->providerPluginManager = $provider_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($data, array $plugins, array $options = []) {
    foreach ($plugins as $plugin_id) {
      $options += [$plugin_id => []];
      $provider = $this->providerPluginManager->createInstance($plugin_id, $options[$plugin_id]);

      try {
        return $provider->geocode($data);
      }
      catch (InvalidCredentials $e) {
        static::log($e->getMessage());
      }
      catch (\Exception $e) {
        static::log($e->getMessage());
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function reverse($latitude, $longitude, array $plugins, array $options = []) {
    foreach ($plugins as $plugin_id) {
      $options += [$plugin_id => []];
      $provider = $this->providerPluginManager->createInstance($plugin_id, $options[$plugin_id]);

      try {
        return $provider->reverse($latitude, $longitude);
      }
      catch (InvalidCredentials $e) {
        static::log($e->getMessage());
      }
      catch (\Exception $e) {
        static::log($e->getMessage());
      }
    }

    return FALSE;
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
