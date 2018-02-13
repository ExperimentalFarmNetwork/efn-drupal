<?php

namespace Drupal\geocoder;

use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a base class for geocoder plugin managers.
 */
abstract class GeocoderPluginManagerBase extends DefaultPluginManager {

  /**
   * Gets a list of available plugins to be used in forms.
   *
   * @return string[]
   *   A list of plugins in a format suitable for form API '#options' key.
   */
  public function getPluginsAsOptions() {
    $options = array_map(function (array $definition) {
      return isset($definition['name']) ? $definition['name'] : $definition['id'];
    }, $this->getDefinitions());
    asort($options);

    return $options;
  }

}
