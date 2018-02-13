<?php

/**
 * @file
 * Generate long form elements.
 */

use Drupal\yamlform\YamlFormInterface;

/**
 * Generate long form elements.
 *
 * @param \Drupal\yamlform\YamlFormInterface $yamlform
 *   A form object.
 *
 * @return array
 *   An array containing long form elements..
 */
function yamlform_test_test_form_long(YamlFormInterface $yamlform) {
  $length = (int) str_replace('test_form_long_', '', $yamlform->id());
  $elements = [];
  for ($i = 1; $i <= $length; $i++) {
    $elements["element_$i"] = [
      '#type' => 'textfield',
      '#title' => (string) t('Element #@index', ['@index' => $i]),
    ];
  }
  return $elements;
}
