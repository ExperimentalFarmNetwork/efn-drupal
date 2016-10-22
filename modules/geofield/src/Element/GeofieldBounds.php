<?php

/**
 * @file
 * Contains \Drupal\geofield\Element\GeofieldBounds.
 */

namespace Drupal\geofield\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a Geofield bounds form element.
 *
 * @FormElement("geofield_bounds")
 */
class GeofieldBounds extends GeofieldElementBase {

  /**
   * {@inheritdoc}
   */
  public static $components = array(
    'top' => array(
      'title' => 'Top',
      'range' => 90,
    ),
    'right' => array(
      'title' => 'Right',
      'range' => 180,
    ),
    'bottom' => array(
      'title' => 'Bottom',
      'range' => 90,
    ),
    'left' => array(
      'title' => 'Left',
      'range' => 180,
    ),
  );

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'elementProcess'),
      ),
      '#element_validate' => array(
        array($class, 'boundsValidate')
      ),
      '#theme' => 'geofield_bounds',
      '#theme_wrappers' => array('fieldset'),
    );
  }

  /**
   * Validates a Geofield bounds element.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function boundsValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    static::elementValidate($element, $form_state, $complete_form);

    $pairs = array(
      array(
        'bigger' => 'top',
        'smaller' => 'bottom',
      ),
      array(
        'bigger' => 'right',
        'smaller' => 'left',
      ),
    );

    foreach ($pairs as $pair) {
      if ($element[$pair['smaller']]['#value'] >= $element[$pair['bigger']]['#value']) {
        $form_state->setError($element[$pair['smaller']], t('@title: @component_bigger must be greater than @component_smaller.', array('@title' => $element['#title'], '@component_bigger' => static::$components[$pair['bigger']]['title'], '@component_smaller' => static::$components[$pair['smaller']]['title'])));
      }
    }
  }

}
