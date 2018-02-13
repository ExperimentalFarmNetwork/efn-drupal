<?php

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\DmsConverter;
use Drupal\geofield\DmsPoint;

/**
 * Plugin implementation of the 'geofield_dms' widget.
 *
 * @FieldWidget(
 *   id = "geofield_dms",
 *   label = @Translation("DMS Widget"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldDmsWidget extends GeofieldBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $latlon_value = [];

    foreach (['lat', 'lon'] as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : '';
    }

    $element += [
      '#type' => 'geofield_dms',
      '#default_value' => $latlon_value,
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      $components = DmsConverter::dmsToDecimal(new DmsPoint($value['value']['lon'], $value['value']['lat']));
      $values[$delta]['value'] = $this->wktGenerator->wktGeneratePoint($components);
    }

    return $values;
  }

}
