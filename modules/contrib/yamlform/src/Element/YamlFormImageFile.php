<?php

namespace Drupal\yamlform\Element;

/**
 * Provides a form element for an 'image_file' element.
 *
 * @FormElement("yamlform_image_file")
 */
class YamlFormImageFile extends YamlFormManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'image/*';

}
