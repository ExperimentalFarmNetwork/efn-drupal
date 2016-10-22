<?php

namespace Drupal\geocoder_geofield\Plugin\Geocoder\Field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\geocoder_field\Plugin\Geocoder\Field\DefaultField;

/**
 * Provides a geofield geocoder field plugin.
 *
 * @GeocoderField(
 *   id = "geofield",
 *   label = @Translation("Geofield field plugin"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldField extends DefaultField {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(FieldConfigInterface $field, array $form, FormStateInterface &$form_state) {
    $element = parent::getSettingsForm($field, $form, $form_state);
    // On geofield the dumper is always 'wkt'.
    $element['dumper'] = [
      '#type' => 'value',
      '#value' => 'wkt',
    ];
    return $element;
  }

}
