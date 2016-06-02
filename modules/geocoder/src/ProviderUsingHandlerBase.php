<?php

/**
 * @file
 * Contains \Drupal\geocoder\ProviderUsingHandlerBase.
 */

namespace Drupal\geocoder;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Provides a base class for providers using handlers.
 */
abstract class ProviderUsingHandlerBase extends ProviderBase {

  /**
   * The provider handler.
   *
   * @var \Geocoder\Provider\Provider
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\Core\Config\ConfigFactoryInterface $config_factory, \Drupal\Core\Cache\CacheBackendInterface $cache_backend) {
    if (empty($plugin_definition['handler'])) {
      throw new InvalidPluginDefinitionException($plugin_id, "Plugin '$plugin_id' should define a handler.");
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  protected function doGeocode($source) {
    return $this->getHandler()->geocode($source);
  }

  /**
   * {@inheritdoc}
   */
  protected function doReverse($latitude, $longitude) {
    return $this->getHandler()->reverse($latitude, $longitude);
  }

  /**
   * Returns the provider handler.
   *
   * @return \Geocoder\Provider\Provider
   */
  protected function getHandler() {
    if (!isset($this->handler)) {
      $definition = $this->getPluginDefinition();
      $reflection_class = new \ReflectionClass($definition['handler']);
      $this->handler = $reflection_class->newInstanceArgs($this->getArguments());
    }

    return $this->handler;
  }

  /**
   * Builds a list of arguments to be used by the handler.
   *
   * @return array
   *   The list of arguments for handler instantiation.
   */
  protected function getArguments() {
    $arguments = [];
    foreach ($this->getPluginDefinition()['arguments'] as $key => $argument) {
      // No default value has been passed.
      if (is_string($key)) {
        $config_name = $key;
        $default_value = $argument;
      }
      else {
        $config_name = $argument;
        $default_value = NULL;
      }
      $arguments[] = isset($this->configuration[$config_name]) ? $this->configuration[$config_name] : $default_value;
    }
    return $arguments;
  }

}
