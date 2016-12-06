<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'buttons_other' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_buttons_other",
 *   label = @Translation("Buttons other"),
 *   category = @Translation("Options elements"),
 * )
 */
class YamlFormButtonsOther extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + self::getOtherProperties();
  }

}
