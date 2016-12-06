<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a roles entity reference form element.
 *
 * @FormElement("yamlform_roles")
 */
class YamlFormRoles extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#element_validate'] = [
      [$class, 'validateYamlFormRoles'],
    ];
    $info['#include_anonymous'] = TRUE;
    return $info;
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#options'] = array_map('\Drupal\Component\Utility\Html::escape', user_role_names());

    // Check if anonymous is included.
    if (empty($element['#include_anonymous'])) {
      unset($element['#options']['anonymous']);
    }

    $element['#attached']['library'][] = 'yamlform/yamlform.element.roles';
    $element['#attributes']['class'][] = 'js-yamlform-roles-role';
    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

  /**
   * Form element validation handler for yamlform_users elements.
   */
  public static function validateYamlFormRoles(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $form_state->getValue($element['#parents'], []);
    $form_state->setValueForElement($element, array_values(array_filter($value)));
  }

}
