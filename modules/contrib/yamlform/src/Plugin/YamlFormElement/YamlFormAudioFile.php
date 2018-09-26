<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'yamlform_audio_file' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_audio_file",
 *   label = @Translation("Audio file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormAudioFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    $formats = parent::getFormats();
    $formats['file'] = $this->t('HTML5 Audio player (MP3 only)');
    return $formats;
  }

}
