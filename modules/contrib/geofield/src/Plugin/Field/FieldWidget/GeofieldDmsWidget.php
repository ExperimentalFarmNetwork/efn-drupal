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
      // Generate a valid Geofield only if the DMS coordinates are valid.
      if (
        is_numeric($value['value']['lon']['degrees']) &&
        is_numeric($value['value']['lon']['minutes']) &&
        is_numeric($value['value']['lon']['seconds']) &&
        is_numeric($value['value']['lat']['degrees']) &&
        is_numeric($value['value']['lat']['minutes']) &&
        is_numeric($value['value']['lat']['seconds'])
      ) {
        $components = DmsConverter::dmsToDecimal(new DmsPoint($value['value']['lon'], $value['value']['lat']));
        $values[$delta]['value'] = $this->wktGenerator->wktGeneratePoint($components);
      }
      // Otherwise delete the entry.
      else {
        $values[$delta]['value'] = NULL;
      }
    }
    return $values;
  }

}
