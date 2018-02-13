<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\Field\FieldFormatter\GeofieldDefaultFormatter.
 */

namespace Drupal\geofield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geofield_default' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_default",
 *   label = @Translation("Raw Output"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'output_format' => 'wkt'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = \Drupal::service('geofield.geophp')->getAdapterMap();
    unset($options['google_geocode']);

    $elements['output_format'] = array(
      '#title' => t('Output Format'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('output_format'),
      '#options' => $options,
      '#required' => TRUE,
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $formatOptions = \Drupal::service('geofield.geophp')->getAdapterMap();
    $summary = array();
    $summary[] = t('Geospatial output format: @format', array('@format' => $formatOptions[$this->getSetting('output_format')]));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $geophp = \Drupal::service('geofield.geophp');

    foreach ($items as $delta => $item) {
      $geom = $geophp->load($item->value);
      $output = $geom ? $geom->out($this->getSetting('output_format')) : '';
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
