<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'yamlform_video_file' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_video_file",
 *   label = @Translation("Video file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormVideoFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    $formats = parent::getFormats();
    $formats['file'] = $this->t('HTML5 Video player (MP4 only)');
    return $formats;
  }

}
