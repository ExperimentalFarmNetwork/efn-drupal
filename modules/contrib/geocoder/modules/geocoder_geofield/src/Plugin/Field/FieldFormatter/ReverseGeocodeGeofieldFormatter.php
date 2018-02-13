<?php

namespace Drupal\geocoder_geofield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder_field\Plugin\Field\GeocodeFormatterBase;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_geofield_reverse_geocode",
 *   label = @Translation("Reverse geocode"),
 *   field_types = {
 *     "geofield",
 *   }
 * )
 */
class ReverseGeocodeGeofieldFormatter extends GeocodeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $geophp = \Drupal::service('geofield.geophp');
    $dumper = \Drupal::service('geocoder.dumper.' . $this->getSetting('dumper_plugin'));
    $provider_plugins = $this->getEnabledProviderPlugins();

    foreach ($items as $delta => $item) {
      /** @var \Geometry $geom */
      $geom = $geophp->load($item->value);
      $centroid = $geom->getCentroid();
      if ($addressCollection = $this->geocoder->reverse($centroid->y(), $centroid->x(), $provider_plugins)) {
        $elements[$delta] = array(
          '#markup' => $dumper->dump($addressCollection->first()),
        );
      }
    }

    return $elements;
  }

}
