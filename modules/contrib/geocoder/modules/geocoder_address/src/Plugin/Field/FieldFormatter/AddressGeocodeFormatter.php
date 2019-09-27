<?php

namespace Drupal\geocoder_address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder_field\Plugin\Field\FieldFormatter\GeocodeFormatter;

/**
 * Plugin implementation of the Geocode formatter.
 *
 * @FieldFormatter(
 *   id = "geocoder_address",
 *   label = @Translation("Geocode address"),
 *   field_types = {
 *     "address",
 *   }
 * )
 */
class AddressGeocodeFormatter extends GeocodeFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
    $provider_plugins = $this->getEnabledProviderPlugins();

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $address = [];

      $address[] = !empty($value['address_line1']) ? $value['address_line1'] : NULL;
      $address[] = !empty($value['address_line2']) ? $value['address_line2'] : NULL;
      $address[] = !empty($value['postal_code']) ? $value['postal_code'] : NULL;
      $address[] = !empty($value['locality']) ? $value['locality'] : NULL;
      $address[] = !empty($value['country']) ? $value['country'] : NULL;

      if ($addressCollection = $this->geocoder->geocode(implode(' ', array_filter($address)), array_keys($provider_plugins))) {
        $elements[$delta] = [
          '#plain_text' => $dumper->dump($addressCollection->first()),
        ];
      }
    }

    return $elements;
  }

}
