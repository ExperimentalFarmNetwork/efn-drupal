<?php

namespace Drupal\select_or_other\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Base class for select or other form elements.
 *
 * Properties:
 * - #multiple: If the widget should allow multiple values to be selected.
 * - #select_type: Either 'list' for a select list and 'buttons' for checkboxes
 *   or radio buttons depending on cardinality.
 * - #merged_values: Set this to true if the widget should return a single array
 *   with the merged values from both the 'select' and 'other' fields.
 * - #options: An associative array, where the keys are the retured values for
 *   each option, and the values are the options to be presented to the user.
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 */
abstract class ElementBase extends FormElement {

  /**
   * Adds an 'other' option to the selectbox.
   */
  protected static function addOtherOption($options) {
    $options['select_or_other'] = 'Other';

    return $options;
  }

  /**
   * Prepares an array to be used as a state in a form API #states array.
   *
   * @param string $state
   *   The state the element should have.
   * @param string $element_name
   *   The name of the element on which this state depends.
   * @param string $value_key
   *   The key used to select the property on which the state depends.
   * @param mixed $value
   *   The value a property should have to trigger the state.
   *
   * @return array
   *   An array with state information to be used in a #states array.
   */
  protected static function prepareState($state, $element_name, $value_key, $value) {
    return [
      $state => [
        ':input[name="' . $element_name . '"]' => [$value_key => $value],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => [[$class, 'processSelectOrOther']],
      '#multiple' => FALSE,
      '#select_type' => 'list',
      '#merged_values' => FALSE,
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#tree' => TRUE,
    );
  }

  /**
   * Render API callback: Expands the select_or_other element type.
   *
   * Expands the select or other element to have a 'select' and 'other' field.
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    self::addSelectField($element);
    self::addOtherField($element);
    return $element;
  }

  /**
   * Adds the 'select' field to the element.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addSelectField(array &$element) {
    $element['select'] = [
      '#default_value' => $element['#default_value'],
      '#required' => $element['#required'],
      '#multiple' => $element['#multiple'],
      '#options' => self::addOtherOption($element['#options']),
      '#weight' => 10,
    ];
  }

  /**
   * Adds the 'other' field to the element.
   *
   * @param array $element
   *   The select or other element.
   */
  protected static function addOtherField(array &$element) {
    $element['other'] = [
      '#type' => 'textfield',
      '#weight' => 20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $values = [];
    if ($input !== FALSE && !empty($input['select'])) {

      if ($element['#multiple']) {
        $values = [
          'select' => (array) $input['select'],
          'other' => !empty($input['other']) ? (array) $input['other'] : [],
        ];

        if (in_array('select_or_other', $values['select'])) {
          $values['select'] = array_diff($values['select'], ['select_or_other']);
        }
        else {
          $values['other'] = [];
        }

        if (isset($element['#merged_values']) && $element['#merged_values']) {
          if (!empty($values['other'])) {
            $values = array_merge($values['select'], $values['other']);
            // Add the other option to the available options to prevent
            // validation errors.
            $element['#options'][$input['other']] = $input['other'];
          }
          else {
            $values = $values['select'];
          }
        }

      }
      else {
        if ($input['select'] === 'select_or_other') {
          $values = [$input['other']];
          // Add the other option to the available options to prevent
          // validation errors.
          $element['#options'][$input['other']] = $input['other'];
        }
        else {
          $values = [$input['select']];
        }
      }
    }

    return $values;
  }

}
