<?php

/**
 * @file
 * Contains \Drupal\geofield\Element\GeofieldLatLon.
 */

namespace Drupal\geofield\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a Geofield Lat Lon form element.
 *
 * @FormElement("geofield_latlon")
 */
class GeofieldLatLon extends GeofieldElementBase {

  /**
   * {@inheritdoc}
   */
  public static $components = array(
    'lat' => array(
      'title' => 'Latitude',
      'range' => 90,
    ),
    'lon' => array(
      'title' => 'Longitude',
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
        array($class, 'latlonProcess'),
      ),
      '#element_validate' => array(
        array($class, 'elementValidate'),
      ),
      '#theme_wrappers' => array('fieldset'),
    );
  }

  /**
   * Generates the Geofield Lat Lon form element.
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
  public static function latlonProcess(&$element, FormStateInterface $form_state, &$complete_form) {
    static::elementProcess($element, $form_state, $complete_form);

    if (!empty($element['#geolocation']) && $element['#geolocation'] == TRUE) {
      $element['#attached']['library'][] = 'geofield/geolocation';
      $element['geocode'] = array(
        '#type' => 'button',
        '#value' => t('Find my location'),
        '#name' => 'geofield-html5-geocode-button',
      );
      $element['#attributes']['class'] = array('auto-geocode');
    }

    return $element;
  }

}
