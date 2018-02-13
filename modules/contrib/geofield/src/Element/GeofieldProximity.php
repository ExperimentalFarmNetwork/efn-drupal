<?php

/**
 * @file
 * Contains \Drupal\geofield\Element\GeofieldProximity.
 */

namespace Drupal\geofield\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a Geofield proximity form element.
 *
 * @FormElement("geofield_proximity")
 */
class GeofieldProximity extends FormElement {

  /**
   * {@inheritdoc}
   */

  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => FALSE,
      '#tree' => TRUE,
      '#process' => array(
        array($class, 'proximityProcess'),
      ),
      '#theme' => 'geofield_proximity',
    );
  }

  /**
   * Generates the Geofield proximity form element..
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
  public static function proximityProcess(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attributes'] = array('class' => array('clearfix'));
    $element['#tree'] = TRUE;
    $element['#attached']['css'] = array(drupal_get_path('module', 'geofield') . '/css/proximity-element.css');

    // Create the textfield for distance.
    $element['distance'] = array(
      '#type' => 'textfield',
      '#title' => t('Distance'),
      '#default_value' => !empty($element['#default_value']['distance']) ? $element['#default_value']['distance'] : '',
      '#title_display' => 'invisible',
      '#element_validate' => array('element_validate_integer_positive'),
    );

    // If #geofield_range is TRUE, create second option for range.
    if (!empty($element['#geofield_range']) && $element['#geofield_range'] == TRUE) {
      $element['distance2'] = array(
        '#type' => 'textfield',
        '#title' => t('Distance End'),
        '#default_value' => !empty($element['#default_value']['distance2']) ? $element['#default_value']['distance2'] : '',
        '#title_display' => 'invisible',
        '#element_validate' => array('element_validate_integer_positive'),
      );
    }

    // Create dropdown for units.
    $element['unit'] = array(
      '#type' => 'select',
      '#options' => geofield_radius_options(),
      '#title' => t('Unit'),
      '#default_value' => !empty($element['#default_value']['unit']) ? $element['#default_value']['unit'] : GEOFIELD_KILOMETERS,
      '#title_display' => 'invisible',
    );

    // Create textfield for geocoded input.
    $element['origin'] = array(
      '#type' => (!empty($element['#origin_element'])) ? $element['#origin_element'] : 'textfield',
      '#title' => t('Origin'),
      '#prefix' => '<span class="geofield-proximity-origin-from">from</span>',
      '#title_display' => 'invisible',
      '#required' => !empty($element['#required']) ? $element['#required'] : FALSE,
      '#default_value' => !empty($element['#default_value']['origin']) ? $element['#default_value']['origin'] : FALSE,
    );

    if (!empty($element['#origin_options'])) {
      $element['origin'] = array_merge($element['origin'], $element['#origin_options']);
    }

    $class = get_called_class();
    if (isset($element['#element_validate'])) {
      array_push($element['#element_validate'], array($class,'boundsValidate'));
    }
    else {
      $element['#element_validate'] = array(array($class,'boundsValidate'));
    }

    return $element;
  }

}
