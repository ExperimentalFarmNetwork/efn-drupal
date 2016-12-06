<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for buttons with an other option.
 *
 * @FormElement("yamlform_buttons_other")
 */
class YamlFormButtonsOther extends YamlFormOtherBase {

  /**
   * {@inheritdoc}
   */
  protected static $type = 'yamlform_buttons';

  /**
   * Processes an 'other' element.
   *
   * See select list form element for select list properties.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processYamlFormOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processYamlFormOther($element, $form_state, $complete_form);
    return $element;
  }

}
