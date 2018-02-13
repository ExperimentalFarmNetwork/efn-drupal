<?php

/**
 * @file
 * Drush integration for the default_content module.
 */

use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function default_content_drush_command() {
  $items['default-content-export'] = [
    'description' => dt('Exports a single entity'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'file' => dt('Write out the exported content to a file instead of stdout'),
    ],
    'aliases' => ['dce'],
    'required-arguments' => 2,
  ];
  $items['default-content-export-references'] = [
    'description' => dt('Exports an entity and all its referenced entities.'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'folder' => dt('Folder to export to, entities are grouped by entity type into directories.'),
    ],
    'aliases' => ['dcer'],
    'required-arguments' => 1,
  ];
  $items['default-content-export-module'] = [
    'description' => dt('Exports all the content defined in a module info file.'),
    'arguments' => [
      'module' => dt('The name of the module.'),
    ],
    'aliases' => ['dcem'],
    'required-arguments' => 1,
  ];
  $items['default-content-import'] = [
    'description' => dt('Imports or updates the content provided by installed modules and default profile.'),
    'arguments' => [
      'module' => dt('The name of the module or profile. If omitted, by all content from enabled modules and default profile will be imported.'),
    ],
    'options' => [
      'update' => dt('Update existing entities.'),
    ],
    'aliases' => ['dci'],
  ];

  return $items;
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export($entity_type_id, $entity_id) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  $export = $manager->exportContent($entity_type_id, $entity_id);

  if ($file = drush_get_option('file')) {
    file_put_contents($file, $export);
  }
  else {
    drush_print($export);
  }
}

/**
 * Exports a piece of content and all its referenced entities.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param mixed $entity_id
 *   (Optional) The entity ID to export or all entities will be exported.
 */
function drush_default_content_export_references($entity_type_id, $entity_id = NULL) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');

  $folder = drush_get_option('folder', '.');
  if (is_null($entity_id) && ($entities = \Drupal::entityQuery($entity_type_id)->execute())) {
    // @todo Add paging.
    foreach ($entities as $entity_id) {
      $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
      $manager->writeDefaultContent($serialized_by_type, $folder);
    }
  }
  else {
    $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
    $manager->writeDefaultContent($serialized_by_type, $folder);
  }
}

/**
 * Exports all of the content for a given module.
 *
 * @param string $module_name
 *   The module name to export.
 *
 * @return null|false
 *   FALSE if the passed module or profile doesn't exist or it's uninstalled.
 */
function drush_default_content_export_module($module_name) {
  if (!_drush_default_content_valid_module($module_name)) {
    return FALSE;
  }
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  $serialized_by_type = $manager->exportModuleContent($module_name);
  $module_folder = \Drupal::moduleHandler()->getModule($module_name)->getPath() . '/content';
  $manager->writeDefaultContent($serialized_by_type, $module_folder);
}

/**
 * Imports or updates content provided in enabled modules and default profile.
 *
 * @param string|null $module
 *   (optional) An installed module or the default profile. If passed, the
 *   import will limit to this module or profile. Defaults to NULL.
 *
 * @return null|false
 *   FALSE if the passed module or profile doesn't exist or it's uninstalled.
 */
function drush_default_content_import($module = NULL) {
  if (!_drush_default_content_valid_module($module)) {
    return FALSE;
  }
  $update_existing = drush_get_option('update', FALSE);
  $modules = !empty($module) ? [$module] : array_keys(\Drupal::config('core.extension')->get('module'));
  $default_content_manager = \Drupal::service('default_content.manager');
  $total = 0;
  $translation = \Drupal::translation();
  foreach ($modules as $module) {
    if ($count = count($default_content_manager->importContent($module, $update_existing))) {
      drush_log($translation->formatPlural($count, '@module: 1 entry', '@module: @count entries', ['@module' => $module]), LogLevel::OK);
      $total += $count;
    }
  }
  if ($total) {
    drush_log($translation->formatPlural($total, 'Total: 1 entry', 'Total: @count entries'), LogLevel::OK);
  }
  else {
    drush_log(dt('No content has been imported.'), LogLevel::WARNING);
  }
}

/**
 * Validates that a module or a profile exists and is installed.
 *
 * @param string|null $module
 *   An installed module or the default profile. If not passed, the validation
 *   passes.
 *
 * @return bool
 *   TRUE if the passed module or profile name validates.
 */
function _drush_default_content_valid_module($module) {
  if (!empty($module) && !\Drupal::moduleHandler()->moduleExists($module)) {
    return drush_set_error('INVALID_MODULE', dt("Module or profile '@module' doesn't exist or is uninstalled.", ['@module' => $module]));
  }
  return TRUE;
}
