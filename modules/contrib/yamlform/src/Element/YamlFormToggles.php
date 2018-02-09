<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a form element for toggles.
 *
 * @FormElement("yamlform_toggles")
 */
class YamlFormToggles extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return YamlFormToggle::getDefaultProperties() + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processCheckboxes($element, $form_state, $complete_form);

    // Convert checkboxes to toggle elements.
    foreach (Element::children($element) as $key) {
      $element[$key]['#type'] = 'yamlform_toggle';
      $element[$key] += array_intersect_key($element, YamlFormToggle::getDefaultProperties());
    }

    return $element;
  }

}
