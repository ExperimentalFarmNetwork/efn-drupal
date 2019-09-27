<?php

namespace Drupal\geocoder_geofield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder_field\Plugin\Field\FieldFormatter\GeocodeFormatter;

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
class ReverseGeocodeGeofieldFormatter extends GeocodeFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
    $provider_plugins = $this->getEnabledProviderPlugins();

    /** @var \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp */
    $geophp = \Drupal::service('geofield.geophp');

    foreach ($items as $delta => $item) {
      /** @var \Geometry $geom */
      $geom = $geophp->load($item->value);

      /** @var \Point $centroid */
      $centroid = $geom->getCentroid();

      if ($address_collection = $this->geocoder->reverse($centroid->y(), $centroid->x(), array_keys($provider_plugins))) {
        $elements[$delta] = [
          '#markup' => $dumper->dump($address_collection->first()),
        ];
      }
    }

    return $elements;
  }

}
