<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'managed_file' element.
 *
 * @YamlFormElement(
 *   id = "managed_file",
 *   api = "https://api.drupal.org/api/drupal/core!modules!file!src!Element!ManagedFile.php/class/ManagedFile",
 *   label = @Translation("Managed file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class ManagedFile extends YamlFormManagedFileBase {}
