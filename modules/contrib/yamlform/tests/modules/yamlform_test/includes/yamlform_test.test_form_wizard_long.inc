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
 *   An array containing long form elements.
 */
function yamlform_test_test_form_wizard_long(YamlFormInterface $yamlform) {
  $wizard_index = 1;
  $length = (int) str_replace('test_form_wizard_long_', '', $yamlform->id());
  $elements = [];
  for ($i = 1; $i <= $length; $i++) {
    if (!isset($elements["wizard_$wizard_index"])) {
      $elements["wizard_$wizard_index"] = [
        '#type' => 'wizard_page',
        '#title' => (string) t('Wizard page #@index', ['@index' => $wizard_index]),
      ];
    }

    $elements["wizard_$wizard_index"]["element_$i"] = [
      '#type' => 'textfield',
      '#title' => (string) t('Element #@index', ['@index' => $i]),
    ];

    if ($i % 10 === 0) {
      $wizard_index++;
    }
  }

  return $elements;
}
