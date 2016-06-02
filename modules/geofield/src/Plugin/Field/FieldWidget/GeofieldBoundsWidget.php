<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\Field\FieldWidget\GeofieldBoundsWidget.
 */

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geofield_bounds' widget.
 *
 * @FieldWidget(
 *   id = "geofield_Bounds",
 *   label = @Translation("Bounding box"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldBoundsWidget extends WidgetBase {

  /**
   * Bounds widget components.
   *
   * @var array
   */
  public $components = array('top', 'right', 'bottom', 'left');

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $bounds_value = array();

    foreach ($this->components as $component) {
      $bounds_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : '';
    }

    $element += array(
      '#type' => 'geofield_bounds',
      '#default_value' => $bounds_value,
    );

    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      foreach ($this->components as $component) {
        if (empty($value['value'][$component]) && !is_numeric($value['value'][$component])) {
          $values[$delta]['value'] = '';
          continue 2;
        }
      }
      $components = $value['value'];
      $bounds = array(
        array($components['right'], $components['top']),
        array($components['right'], $components['bottom']),
        array($components['left'], $components['bottom']),
        array($components['left'], $components['top']),
        array($components['right'], $components['top']),
      );

      $values[$delta]['value'] = \Drupal::service('geofield.wkt_generator')->WktBuildPolygon($bounds);
    }

    return $values;
  }

}
