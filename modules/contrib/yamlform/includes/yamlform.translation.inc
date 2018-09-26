<?php

/**
 * @file
 * YAML Form module translation hooks.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Utility\YamlFormTidy;
use Drupal\Core\Serialization\Yaml;
use Drupal\yamlform\Utility\YamlFormElementHelper;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function yamlform_form_config_translation_add_form_alter(&$form, FormStateInterface $form_state, $is_new = TRUE) {
  // Manually apply YAML editor to text field that store YAML data.
  foreach ($form['config_names'] as $config_name => &$config_element) {
    if ($config_name == 'yamlform.settings') {
      _yamlform_form_config_translate_add_form_alter_yaml_element($config_element['test']['types']);
      _yamlform_form_config_translate_add_form_alter_yaml_element($config_element['test']['names']);
    }
    elseif (strpos($config_name, 'yamlform.yamlform_options.') === 0) {
      _yamlform_form_config_translate_add_form_alter_yaml_element($config_element['options']);
    }
    elseif (strpos($config_name, 'yamlform.yamlform.') === 0) {
      $yamlform_id = str_replace('yamlform.yamlform.', '', $config_name);
      $yamlform = YamlForm::load($yamlform_id);

      /** @var \Drupal\yamlform\YamlFormTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('yamlform.translation_manager');
      $translation_langcode = $form_state->get('config_translation_language')->getId();;
      $source_elements = $translation_manager->getSourceElements($yamlform);
      $translation_elements = $translation_manager->getTranslationElements($yamlform, $translation_langcode);
      $source_value = trim(Yaml::encode($source_elements));
      $translation_value = trim(Yaml::encode($translation_elements));

      _yamlform_form_config_translate_add_form_alter_yaml_element($config_element['elements'], $source_value, $translation_value);


      $form['inputs'] = [
        '#type' => 'textarea',
        '#parents' => ["translation", "config_names", "yamlform.yamlform.test_translation", "description"],
      ];

      $config_element['elements']['translation']['#description'] = t('Please note: Custom properties will be automatically removed.');
      $form_state->set('yamlform_config_name', $config_name);
      $form_state->set('yamlform_source_elements', $source_elements);

      $form['#validate'][] = '_yamlform_form_config_translate_add_form_validate';

    }
  }
}

/**
 * Validate callback; Validates and cleanups form elements.
 */
function _yamlform_form_config_translate_add_form_validate(&$form, FormStateInterface $form_state) {
  $values = $form_state->getValues();

  $config_name = $form_state->get('yamlform_config_name');
  $source_elements = $form_state->get('yamlform_source_elements');
  $submitted_translation_elements = Yaml::decode($values['translation']['config_names'][$config_name]['elements']);
  $translation_elements = $source_elements;

  // Remove all custom translation properties.
  YamlFormElementHelper::merge($translation_elements, $submitted_translation_elements);

  // Remove any translation property that has not been translated.
  _yamlform_form_config_translate_add_form_filter_elements($translation_elements, $source_elements);

  // Update form value.
  $values['translation']['config_names'][$config_name]['elements'] = ($translation_elements) ? Yaml::encode($translation_elements) : '';
  $form_state->setValues($values);
}

/**
 * Merge element properties.
 *
 * @param array $translation_elements
 *   An array of elements.
 * @param array $source_elements
 *   An array of elements to be merged.
 */
function _yamlform_form_config_translate_add_form_filter_elements(array &$translation_elements, array $source_elements) {
  foreach ($translation_elements as $key => &$translation_element) {
    if (!isset($source_elements[$key])) {
      continue;
    }
    $source_element = $source_elements[$key];
    if ($translation_element == $source_element) {
      unset($translation_elements[$key]);
    }
    elseif (is_array($translation_element)) {
      _yamlform_form_config_translate_add_form_filter_elements($translation_element, $source_element);
      if (empty($translation_element)) {
        unset($translation_elements[$key]);
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function yamlform_form_config_translation_edit_form_alter(&$form, FormStateInterface $form_state) {
  yamlform_form_config_translation_add_form_alter($form, $form_state, FALSE);
}

/**
 * Alter translated config entity property.
 *
 * @param array $element
 *   A form element containing 'source' and 'translation'.
 * @param string $source_value
 *   (optional) The custom config source value.
 * @param string $translation_value
 *   (optional) The custom config translation value.
 */
function _yamlform_form_config_translate_add_form_alter_yaml_element(array &$element, $source_value = NULL, $translation_value = NULL) {
  // Source.
  $element['source']['#wrapper_attributes']['class'][] = 'yamlform-translation-source';
  $element['source']['value'] = [
    '#type' => 'yamlform_codemirror',
    '#mode' => 'yaml',
    '#value' => YamlFormTidy::tidy($source_value ?: trim(strip_tags($element['source']['#markup']))),
    '#disabled' => TRUE,
    '#attributes' => ['readonly' => TRUE],
  ];
  unset($element['source']['#markup']);

  // Translation.
  $element['translation']['#type'] = 'yamlform_codemirror';
  $element['translation']['#mode'] = 'yaml';
  if ($translation_value) {
    $element['translation']['#default_value'] = $translation_value;
  }
  $element['translation']['#default_value'] = trim($element['translation']['#default_value']);
  $element['#attached']['library'][] = 'yamlform/yamlform.admin.translation';
}
