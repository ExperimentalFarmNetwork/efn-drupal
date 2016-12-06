<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element for buttons with an other option.
 *
 * @FormElement("yamlform_buttons")
 */
class YamlFormButtons extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processRadios($element, $form_state, $complete_form);
    $element['#attached']['library'][] = 'yamlform/yamlform.element.buttons';
    $element['#attributes']['class'][] = 'js-yamlform-buttons';
    $element['#attributes']['class'][] = 'yamlform-buttons';
    $element['#options_display'] = 'side_by_side';
    return $element;
  }

}
