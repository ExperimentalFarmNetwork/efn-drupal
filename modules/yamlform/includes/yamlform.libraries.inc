<?php

/**
 * @file
 * Form libraries.
 */

/**
 * Implements hook_library_info_alter().
 */
function yamlform_library_info_alter(&$libraries, $extension) {
  // Only alter form libraries.
  if ($extension != 'yamlform') {
    return;
  }

  // Map /library/* paths to CDN.
  // @see yamlform.libraries.yml.
  foreach ($libraries as &$library) {
    // Check CDN setting exists.
    if (!isset($library['cdn'])) {
      continue;
    }

    // Check if the CDN's source /library/* path exists.
    reset($library['cdn']);
    if (file_exists(DRUPAL_ROOT . key($library['cdn']))) {
      continue;
    }

    _yamlform_library_info_alter_recursive($library, $library['cdn']);
  }
}

/**
 * Recursive through a form library.
 *
 * @param array $library
 *   A form library defined in yamlform.libraries.yml.
 * @param array $cdn
 *   A associative array of library paths mapped to CDN URL.
 */
function _yamlform_library_info_alter_recursive(array &$library, array $cdn) {
  foreach ($library as $key => &$value) {
    // CSS and JS files and listed in associative arrays keyed via string.
    if (!is_string($key) || !is_array($value)) {
      continue;
    }

    // Ignore the CDN's associative array.
    if ($key == 'cdn') {
      continue;
    }

    // Replace the CDN sources (ie /library/*) with the CDN URL destination
    // (https://cdnjs.cloudflare.com/ajax/libs/*).
    foreach ($cdn as $source => $destination) {
      if (strpos($key, $source) === 0) {
        $uri = str_replace($source, $destination, $key);
        $library[$uri] = $value;
        unset($library[$key]);
        break;
      }
    }

    // Recurse downward to find nested libraries.
    _yamlform_library_info_alter_recursive($value, $cdn);
  }
}
