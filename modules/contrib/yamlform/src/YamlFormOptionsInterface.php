<?php

namespace Drupal\yamlform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a form options entity.
 */
interface YamlFormOptionsInterface extends ConfigEntityInterface {

  /**
   * Get options (YAML) as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE is options YAML is invalid.
   */
  public function getOptions();

  /**
   * Determine if the form options has alter hooks.
   *
   * @return bool
   *   TRUE if the form options has alter hooks.
   */
  public function hasAlterHooks();

  /**
   * Get form element options.
   *
   * @param array $element
   *   A form element.
   * @param string $property_name
   *   The element property containing the options. Defaults to #options,
   *   for yamlform_likert elements it is #answers.
   *
   * @return array
   *   An associative array of options.
   */
  public static function getElementOptions(array $element, $property_name = '#options');

}
