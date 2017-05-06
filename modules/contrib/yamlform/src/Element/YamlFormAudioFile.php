<?php

namespace Drupal\yamlform\Element;

/**
 * Provides a form element for an 'audio_file' element.
 *
 * @FormElement("yamlform_audio_file")
 */
class YamlFormAudioFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'audio/*';

}
