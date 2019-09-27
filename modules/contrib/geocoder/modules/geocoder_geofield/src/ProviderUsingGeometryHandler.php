<?php

namespace Drupal\geocoder_geofield;

use Drupal\geocoder\ProviderUsingHandlerBase;

/**
 * Provides a base class for providers using handlers.
 */
abstract class ProviderUsingGeometryHandler extends ProviderUsingHandlerBase {

  /**
   * Returns the provider handler.
   *
   * @return \Drupal\geocoder_geofield\Geocoder\Provider\GeometryProviderInterface
   *   The provider plugin.
   *
   * @throws \ReflectionException
   */
  protected function getHandler(): GeometryProviderInterface {
    if ($this->handler === NULL) {
      $definition = $this->getPluginDefinition();
      $reflection_class = new \ReflectionClass($definition['handler']);
      $this->handler = $reflection_class->newInstanceArgs($this->getArguments());
    }

    return $this->handler;
  }

  /**
   * Returns the V4 Stateful wrapper.
   *
   * @return \Geocoder\StatefulGeocoder
   *   The current handler wrapped in this class.
   *
   * @throws \ReflectionException
   */
  protected function getHandlerWrapper(): StatefulGeocoder {
    if ($this->handlerWrapper === NULL) {
      $this->handlerWrapper = new StatefulGeocoder(
        $this->getHandler(),
        $this->languageManager->getCurrentLanguage()->getId()
      );
    }

    return $this->handlerWrapper;
  }

  /**
   * Builds a list of arguments to be used by the handler.
   *
   * @return array
   *   The list of arguments for handler instantiation.
   */
  protected function getArguments(): array {
    $arguments = [];

    foreach ($this->getPluginDefinition()['arguments'] as $key => $argument) {
      // No default value has been passed.
      if (\is_string($key)) {
        $config_name = $key;
        $default_value = $argument;
      }
      else {
        $config_name = $argument;
        $default_value = NULL;
      }

      $arguments[] = $this->configuration[$config_name] ?? $default_value;
    }

    return $arguments;
  }

}
