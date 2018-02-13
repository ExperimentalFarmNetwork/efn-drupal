<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a 'details' element.
 *
 * @YamlFormElement(
 *   id = "details",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Details.php/class/Details",
 *   label = @Translation("Details"),
 *   category = @Translation("Containers"),
 * )
 */
class Details extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Form display.
      'open' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);

    if (isset($element['#yamlform_key'])) {
      $element['#attributes']['data-yamlform-key'] = $element['#yamlform_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#yamlform_key'];
    return ["details[data-yamlform-key=\"$name\"]" => $title . '  [' . $this->getPluginLabel() . ']'];
  }

}
