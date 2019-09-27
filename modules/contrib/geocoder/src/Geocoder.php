<?php

namespace Drupal\geocoder;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;

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
  public function geocode($data, array $plugins, array $options = []) {

    // Retrieve plugins options from the module configurations.
    $plugins_options = $this->config->get('plugins_options') ?: [];

    // Merge possible options overrides into plugins options.
    $plugins_options = NestedArray::mergeDeep($plugins_options, $options);

    foreach ($plugins as $plugin_id) {
      // Transform in empty array a null value for the plugin id options.
      $plugins_options += [$plugin_id => []];

      try {
        $provider = $this->providerPluginManager->createInstance($plugin_id, $plugins_options[$plugin_id]);
        return $provider->geocode($data);
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

    // Retrieve plugins options from the module configurations.
    $plugins_options = $this->config->get('plugins_options');

    // Merge possible options overrides into plugins options.
    $plugins_options = NestedArray::mergeDeep($plugins_options, $options);

    foreach ($plugins as $plugin_id) {
      // Transform in empty array a null value for the plugin id options.
      $plugins_options += [$plugin_id => []];

      try {
        $provider = $this->providerPluginManager->createInstance($plugin_id, $plugins_options[$plugin_id]);
        return $provider->reverse($latitude, $longitude);
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
    \Drupal::logger('geocoder')->warning($message);
  }

}
