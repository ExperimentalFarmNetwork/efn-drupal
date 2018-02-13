<?php

/**
 * @file
 * Generate examples of all elements.
 */

/**
 * Generate examples of all composite elements.
 *
 * @return array
 *   An array containing examples of all elements.
 */
function yamlform_test_example_elements_composite() {
  /** @var \Drupal\yamlform\YamlFormElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.yamlform.element');

  $definitions = $element_manager->getDefinitions();
  $definitions = $element_manager->getSortedDefinitions($definitions);
  $elements = $element_manager->getInstances();

  $data = [];
  foreach ($definitions as $definition) {
    $element_type = $definition['id'];

    $yamlform_element = $elements[$element_type];
    $element = _yamlform_test_get_example_element($element_type);
    if (!$element) {
      continue;
    }

    $category_name = (string) $yamlform_element->getPluginDefinition()['category'] ?: 'Other elements';
    if ($category_name != 'Composite elements') {
      continue;
    }

    $data[$element_type . '_example'] = [
      '#type' => 'details',
      '#title' => $element['#title'] . '',
      '#open' => TRUE,
    ];

    $data[$element_type . '_example'][$element_type] = $element;
  }
  return $data;
}
