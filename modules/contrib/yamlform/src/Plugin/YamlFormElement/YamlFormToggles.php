<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a 'toggles' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_toggles",
 *   label = @Translation("Toggles"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 * )
 */
class YamlFormToggles extends OptionsBase {

  use YamlFormToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as &$text) {
      $text .= ' [' . $this->t('Toggle') . ']';
    }
    return $selectors;
  }

}
