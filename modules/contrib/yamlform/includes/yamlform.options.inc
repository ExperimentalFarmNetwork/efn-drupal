<?php

/**
 * @file
 * Options alter hooks.
 */

use Drupal\yamlform\Utility\YamlFormOptionsHelper;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Language\LanguageManager;

/**
 * Implements hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter().
 *
 * @see config/install/yamlform.yamlform.example_options.yml
 */
function yamlform_yamlform_options_range_alter(array &$options, array $element = []) {
  $element += [
    '#min' => 1,
    '#max' => 100,
    '#step' => 1,
    '#pad_length' => NULL,
    '#pad_str' => 0,
  ];

  $options = YamlFormOptionsHelper::range(
    $element['#min'],
    $element['#max'],
    $element['#step'],
    $element['#pad_length'],
    $element['#pad_str']
  );
}

/**
 * Implements hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter().
 */
function yamlform_yamlform_options_time_zones_alter(array &$options, array $element = []) {
  $options = system_time_zones();
}

/**
 * Implements hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter().
 */
function yamlform_yamlform_options_country_codes_alter(array &$options, array $element = []) {
  $options = CountryManager::getStandardList();
}

/**
 * Implements hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter().
 */
function yamlform_yamlform_options_country_names_alter(array &$options, array $element = []) {
  $countries = CountryManager::getStandardList();
  $options = array_combine($countries, $countries);
}

/**
 * Implements hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter().
 */
function yamlform_yamlform_options_languages_alter(array &$options, array $element = []) {
  $languages = LanguageManager::getStandardLanguageList();
  unset($languages['en-x-simple']);
  $options = [];
  foreach ($languages as $language) {
    $options[$language[0]] = $language[0];
  }
}
