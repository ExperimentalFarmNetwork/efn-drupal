<?php

namespace Drupal\geofield\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a base class for Geofield Form elements.
 */
abstract class GeofieldElementBase extends FormElement {

  /**
   * Array declaring components.
   *
   * @var array
   */
  public static $components = [];

  /**
   * Generates a Geofield generic component based form element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function elementProcess(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    $element['#input'] = TRUE;

    foreach (static::$components as $name => $component) {
      $element[$name] = [
        '#type' => 'textfield',
        // The t func is needed to make component translatable throw interface.
        '#title' => t($component['title']),
        '#required' => (!empty($element['#required'])) ? $element['#required'] : FALSE,
        '#default_value' => (isset($element['#default_value'][$name])) ? $element['#default_value'][$name] : '',
        '#attributes' => [
          'class' => ['geofield-' . $name],
        ],
      ];
    }

    unset($element['#value']);
    // Set this to false always to prevent notices.
    $element['#required'] = FALSE;

    return $element;
  }

  /**
   * Validates a Geofield generic component based form element.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function elementValidate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $error_label = isset($element['#error_label']) ? $element['#error_label'] : $element['#title'];
    foreach (static::$components as $key => $component) {
      if (!empty($element[$key]['#value']) && !is_numeric($element[$key]['#value'])) {
        $form_state->setError($element[$key], t('@title: @component_title is not valid.', ['@title' => $error_label, '@component_title' => $component['title']]));
      }
      elseif (abs($element[$key]['#value']) > $component['range']) {
        $form_state->setError($element[$key], t('@title: @component_title is out of bounds (@bounds).', [
          '@title' => $error_label,
          '@component_title' => $component['title'],
          '@bounds' => '+/- ' . $component['range'],
        ]));
      }
    }
  }
}
