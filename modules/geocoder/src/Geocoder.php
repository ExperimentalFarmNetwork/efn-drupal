<?php

/**
 * @file
 * Contains \Drupal\geocoder\Geocoder.
 */

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
   * @param \Drupal\geocoder\ProviderPluginManager
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
        self::log($e->getMessage(), 'error');
      }
      catch (\Exception $e) {
        self::log($e->getMessage(), 'error');
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
        self::log($e->getMessage(), 'error');
      } catch (\Exception $e) {
        self::log($e->getMessage(), 'error');
      }
    }

    return FALSE;
  }

  /**
   * Log a message in the Drupal watchdog and on screen.
   *
   * @param string $message
   *   The message
   * @param string $type
   *   The type of message
   */
  public static function log($message, $type) {
    \Drupal::logger('geocoder')->error($message);
    drupal_set_message($message, $type);
  }

}
