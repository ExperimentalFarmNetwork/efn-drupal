<?php

namespace Drupal\yamlform\Element;

/**
 * Provides a form element for an 'video_file' element.
 *
 * @FormElement("yamlform_video_file")
 */
class YamlFormVideoFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'video/*';

}
