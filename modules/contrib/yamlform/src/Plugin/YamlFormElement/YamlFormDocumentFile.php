<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'yamlform_document_file' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_document_file",
 *   label = @Translation("Document file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormDocumentFile extends YamlFormManagedFileBase {}
