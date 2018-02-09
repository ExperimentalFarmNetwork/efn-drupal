<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'toggle' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_toggle",
 *   label = @Translation("Toggle"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class YamlFormToggle extends Checkbox {

  use YamlFormToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
    ];
    $properties['title_display'] = 'after';
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array &$element, $value, array $options = []) {
    $format = $this->getFormat($element);

    switch ($format) {
      case 'value';
        $on_text = (!empty($element['#on_text'])) ? $element['#on_text'] : $this->t('Yes');
        $off_text = (!empty($element['#off_text'])) ? $element['#off_text'] : $this->t('No');
        return ($value) ? $on_text : $off_text;

      case 'raw';
      default:
        return ($value) ? 1 : 0;
    }
  }

}
