<?php

namespace Drupal\geocoder_field\Plugin\Field\FieldFormatter;

use Drupal\geocoder_field\Plugin\Field\GeocodeFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_geocode_formatter",
 *   label = @Translation("Geocode"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   }
 * )
 */
class GeocodeFormatter extends GeocodeFormatterBase {

  /**
   * Geocoder Plugins not compatible with this Formatter Filed Types..
   *
   * @var array
   */
  protected $incompatiblePlugins = [
    'file',
    'gpxfile',
    'kmlfile',
    'geojsonfile',
  ];

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Filter out the Geocoder Plugins that are not compatible with the Geocode
    // Formatter action.
    $element['plugins'] = array_filter($element['plugins'], function ($e) {
      return !in_array($e, $this->incompatiblePlugins);
    }, ARRAY_FILTER_USE_KEY);

    return $element;

  }

}
