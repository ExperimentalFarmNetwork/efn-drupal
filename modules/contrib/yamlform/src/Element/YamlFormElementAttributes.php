<?php

namespace Drupal\yamlform\Element;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Serialization\Yaml;
use Drupal\yamlform\Utility\YamlFormTidy;

/**
 * Provides a form element for element attributes.
 *
 * @FormElement("yamlform_element_attributes")
 */
class YamlFormElementAttributes extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processYamlFormElementAttributes'],
      ],
      '#theme_wrappers' => ['container'],
      '#classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => []];
    $element['#default_value'] += [
      'class' => [],
      'style' => '',
    ];
    return NULL;
  }

  /**
   * Processes element attributes.
   */
  public static function processYamlFormElementAttributes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Determine what type of HTML element the attributes are being applied to.
    $type = t('element');
    $types = [preg_quote(t('form')), preg_quote(t('link')), preg_quote(t('button'))];
    if (preg_match('/\b(' . implode('|', $types) . ')\b/i', $element['#title'], $match)) {
      $type = $match[1];
    }

    $t_args = [
      '@title' => $element['#title'],
      '@type' => Unicode::strtolower($type),
    ];

    // Class.
    $element['#classes'] = trim($element['#classes']);
    if ($element['#classes']) {
      $classes = preg_split('/\r?\n/', $element['#classes']);
      $element['class'] = [
        '#type' => 'yamlform_select_other',
        '#title' => t('@title CSS classes', $t_args),
        '#description' => t("Apply classes to the @type. Select 'custom...' to enter custom classes.", $t_args),
        '#multiple' => TRUE,
        '#options' => [YamlFormSelectOther::OTHER_OPTION => t('custom...')] + array_combine($classes, $classes),
        '#other__option_delimiter' => ' ',
        '#attributes' => [
          'class' => [
            'js-yamlform-select2',
            'yamlform-select2',
            'js-' . $element['#id'] . '-attributes-style',
          ],
        ],
        '#attached' => ['library' => ['yamlform/yamlform.element.select2']],
        '#default_value' => $element['#default_value']['class'],
      ];

      // ISSUE:
      // Nested element with #element_validate callback that alter an
      // element's value can break the returned value.
      //
      // WORKAROUND:
      // Manually process the 'yamlform_select_other' element.
      $element['class'] = YamlFormSelectOther::valueCallback($element['class'], FALSE, $form_state);
      $element['class'] = YamlFormSelectOther::processYamlFormOther($element['class'], $form_state, $complete_form);
      $element['class']['#type'] = 'item';
      unset($element['class']['#element_validate']);
    }
    else {
      $element['class'] = [
        '#type' => 'textfield',
        '#title' => t('@title CSS classes', $t_args),
        '#description' => t("Apply classes to the @type.", $t_args),
        '#default_value' => implode(' ', $element['#default_value']['class']),
      ];
    }

    // Custom options.
    $element['custom'] = [
      '#type' => 'texfield',
      '#placeholder' => t('Enter custom classes...'),
      '#states' => [
        'visible' => [
          'select.js-' . $element['#id'] . '-attributes-style' => ['value' => '_custom_'],
        ],
      ],
      '#error_no_message' => TRUE,
      '#default_value' => '',
    ];

    // Style.
    $element['style'] = [
      '#type' => 'textfield',
      '#title' => t('@title CSS style', $t_args),
      '#description' => t('Apply custom styles to the @type.', $t_args),
      '#default_value' => $element['#default_value']['style'],
    ];

    // Attributes.
    $attributes = $element['#default_value'];
    unset($attributes['class'], $attributes['style']);
    $element['attributes'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => t('@title custom attributes (YAML)', $t_args),
      '#description' => t('Enter additional attributes to be added the @type.', $t_args),
      '#attributes__access' => (!\Drupal::moduleHandler()->moduleExists('yamlform_ui') || \Drupal::currentUser()->hasPermission('edit yamlform source')),
      '#default_value' => YamlFormTidy::tidy(Yaml::encode($attributes)),
    ];

    // Apply custom properties. Typically used for descriptions.
    foreach ($element as $key => $value) {
      if (strpos($key, '__') !== FALSE) {
        list($element_key, $property_key) = explode('__', ltrim($key, '#'));
        $element[$element_key]["#$property_key"] = $value;
      }
    }

    $element['#element_validate'] = [[get_called_class(), 'validateYamlFormElementAttributes']];

    return $element;
  }

  /**
   * Validates element attributes.
   */
  public static function validateYamlFormElementAttributes(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = $element['#value'];

    $attributes = [];

    if ($values['class']) {
      if (isset($element['class']['select'])) {
        $class = $element['class']['select']['#value'];
        $class_other = $element['class']['other']['#value'];
        if (isset($class[YamlFormSelectOther::OTHER_OPTION])) {
          unset($class[YamlFormSelectOther::OTHER_OPTION]);
          $class[$class_other] = $class_other;
        }
        if ($class) {
          $attributes['class'] = array_values($class);
        }
      }
      else {
        $attributes['class'] = [$values['class']];
      }
    }

    if ($values['style']) {
      $attributes['style'] = $values['style'];
    }

    if (!empty($values['attributes'])) {
      $attributes += Yaml::decode($values['attributes']);
    }

    $form_state->setValueForElement($element['class'], NULL);
    $form_state->setValueForElement($element['style'], NULL);
    $form_state->setValueForElement($element['attributes'], NULL);
    $form_state->setValueForElement($element, $attributes);
  }

}
