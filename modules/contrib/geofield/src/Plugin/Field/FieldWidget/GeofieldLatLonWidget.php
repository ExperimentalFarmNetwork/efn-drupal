<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\Field\FieldWidget\GeofieldLatLonWidget.
 */

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geofield_latlon' widget.
 *
 * @FieldWidget(
 *   id = "geofield_latlon",
 *   label = @Translation("Latitude/Longitude"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldLatLonWidget extends WidgetBase {

  /**
   * Lat Lon widget components.
   *
   * @var array
   */
  public $components = array('lon', 'lat');

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'html5_geolocation' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['html5_geolocation'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use HTML5 Geolocation to set default values',
      '#default_value' => $this->getSetting('html5_geolocation'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array(
      t('HTML5 Geolocation button is @state', array('@state' => $this->getSetting('html5_geolocation') ? t('enabled') : t('disabled')))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $latlon_value = array();

    foreach ($this->components as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : '';
    }

    $element += array(
      '#type' => 'geofield_latlon',
      '#default_value' => $latlon_value,
      '#geolocation' => $this->getSetting('html5_geolocation'),
      '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
    );

    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      foreach ($this->components as $component) {
        if (empty($value['value'][$component]) || !is_numeric($value['value'][$component])) {
          $values[$delta]['value'] = '';
          continue 2;
        }
      }
      $components = $value['value'];
      $values[$delta]['value'] = \Drupal::service('geofield.wkt_generator')->WktBuildPoint(array($components['lon'], $components['lat']));
    }

    return $values;
  }

}
