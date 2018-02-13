<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'yamlform_tableselect_sort' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_tableselect_sort",
 *   label = @Translation("Tableselect sort"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormTableSelectSort extends OptionsBase {

  use YamlFormTableTrait;

  /**
   * {@inheritdoc}
   */
  protected $exportDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Table settings.
      'js_select' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'ol';
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return $this->getTableSelectElementSelectorOptions($element, '[checkbox]');
  }

}
