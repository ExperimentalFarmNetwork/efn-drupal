<?php

namespace Drupal\yamlform;

/**
 * Defines an interface for form element translation classes.
 */
interface YamlFormTranslationManagerInterface {

  /**
   * Get form elements for specific language.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   * @param string $langcode
   *   The language code for the form elements.
   * @param bool $reset
   *   (optional) Whether to reset the translated config cache. Defaults to
   *   FALSE.
   *
   * @return array
   *   A form's translated elements.
   */
  public function getConfigElements(YamlFormInterface $yamlform, $langcode, $reset = FALSE);

  /**
   * Get base form elements from the site's default language.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   *
   * @return array
   *   Base form elements as a flattened associative array.
   */
  public function getBaseElements(YamlFormInterface $yamlform);

  /**
   * Get flattened associative array of translated element properties.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   *
   * @return array
   *   A associative array of translated element properties.
   */
  public function getSourceElements(YamlFormInterface $yamlform);

  /**
   * Get flattened associative array of translated element properties.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   *
   * @return array
   *   A associative array of translated element properties.
   */
  public function getTranslationElements(YamlFormInterface $yamlform, $langcode);

}
