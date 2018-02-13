<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'yamlform_image_file' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_image_file",
 *   label = @Translation("Image file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormImageFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    $formats = parent::getFormats();
    $formats['file'] = $this->t('Image');
    return $formats;
  }

}
