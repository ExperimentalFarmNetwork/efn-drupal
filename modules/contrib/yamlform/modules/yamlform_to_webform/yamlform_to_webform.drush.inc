<?php

/**
 * @file
 * YAML Form to Webform module drush commands.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 */
function yamlform_to_webform_drush_command() {
  $items = [];

  $items['yamlform-to-webform-migrate'] = [
    'description' => 'Migrate all configuration and data from the YAML Form 8.x-1.x module to Webform 8.x-5.x module.',
    'core' => ['8+'],
    'drupal dependencies' => ['yamlform_to_webform'],
    'examples' => [
      'drush yamlform-to-webform-migrate yamlform' => 'Migrate all configuration and data from the YAML Form 8.x-1.x module to Webform 8.x-5.x module.',
    ],
    'aliases' => ['y2wm'],
  ];

  $items['yamlform-to-webform-convert'] = [
    'description' => 'Convert the YAML Form 8.x-1.x module to Webform 8.x-5.x module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'drush yamlform-to-webform-convert yamlform' => 'Convert the core YAML Form module and all sub modules to use the Webform (webform) namespace.',
    ],
    'aliases' => ['y2wc'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function yamlform_to_webform_drush_help($section) {
  switch ($section) {
    case 'drush:yamlform-to-webform-migrate':
      return dt('Migrate all configuration and data from the YAML Form 8.x-1.x module to Webform 8.x-5.x module.');

    case 'drush:yamlform-to-webform-convert':
      return dt('Convert the YAML Form 8.x-1.x module to Webform 8.x-5.x module.');

    case 'meta:yamlform:title':
      return dt('YAML Form to Webform commands');

    case 'meta:yamlform:summary':
      return dt('Renames YAML Form modules and migrates YAML Form configuration and submission data to Webform 8.x-5.x.');
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_yamlform_to_webform_migrate() {
  if (!drush_confirm(dt('Are you sure you want to migrate from YAML Form 8.x-1.x to Webform 8.x-5.x? The migration may take a few minutes.'))) {
    return drush_user_abort();
  }

  /** @var \Drupal\yamlform_to_webform\YamlFormToWebformMigrateManagerInterface $migrate_manager */
  $migrate_manager = \Drupal::service('yamlform_to_webform.migrate_manager');
  $migrate_manager->migrate();
  drush_cache_rebuild();
  drush_print('YAML Form to Webform migration has completed.');
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_yamlform_to_webform_convert() {
  if (!drush_confirm(dt('Are you sure you want convert the YAML Form 8.x-1.x to a Webform 8.x-1.x module?'))) {
    return drush_user_abort();
  }

  $yamlform_path = drupal_get_path('module', 'yamlform');
  $webform_path = dirname($yamlform_path) . '/webform';

  $yamlform_source = DRUPAL_ROOT . "/$yamlform_path";
  $webform_destination = DRUPAL_ROOT . "/$webform_path";

  $config = Yaml::decode(file_get_contents(drupal_get_path('module', 'yamlform_to_webform') . '/_config/yamlform_to_webform.yml'));

  $dt_args = [
    '@module_path' => $yamlform_path,
    '@webform_path' => $webform_path,
  ];

  // DEBUG: drush_print_r($dt_args);

  // Backup .git directory.
  $git_directory = NULL;
  if (file_exists("$webform_destination/.git")) {
    $git_directory = sys_get_temp_dir() . '/webform_git_backup';
    drush_print(dt('Backing up .git @webform_path...', $dt_args));
    `mv $webform_destination/.git $git_directory`;
  }

  // Remove any existing version of the webform-8.x-5.x module.
  if (file_exists($webform_destination)) {
    drush_print(dt('Deleting @webform_path...', $dt_args));
    file_unmanaged_delete_recursive($webform_destination);
  }

  // Copy yamlform module to webform module namespace.
  drush_print(dt('Copying @module_path to @webform_path...', $dt_args));
  // http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
  `cp -r $yamlform_source $webform_destination`;

  // Move certain files.
  $files = [
    'docs/developers/development.md' => 'DEVELOPMENT.md',
    'docs/support/issues.md' => 'ISSUES.md',
    'docs/features.md' => 'FEATURES.md',
  ];
  foreach ($files as $source => $destination) {
    file_unmanaged_move("$webform_destination/$source", "$webform_destination/$destination", FILE_EXISTS_REPLACE);
  }

  // Delete certain directories immediately.
  $directories = [
    '.DS_Store',
    '.git',
    '.idea',
    'docs',
    'GruntFile.js',
    'mkdocs.yml',
    'node_modules',
    'includes/yamlform.update.inc',
    'modules/yamlform_to_webform',
    'modules/yamlform_ui/yamlform_ui.install',

  ];
  foreach ($directories as $directory) {
    if (file_exists("$webform_destination/$directory")) {
      if (drush_get_option(['verbose', 'debug'], FALSE)) {
        drush_print(dt('Deleting @directory...', ['@directory' => "$webform_destination/$directory"]));
      }
      file_unmanaged_delete_recursive("$webform_destination/$directory");
    }
  }
  $files = file_scan_directory($webform_destination, '/(.*\.patch$)/');
  foreach ($files as $file) {
    if (drush_get_option(['verbose', 'debug'], FALSE)) {
      drush_print(dt('Deleting @file...', ['@file' => $file->uri]));
    }
  }

  // Restore .git directory.
  if (file_exists($git_directory)) {
    drush_print(dt('Restoring .git @webform_path...', $dt_args));
    `mv $git_directory $webform_destination/.git `;
  }

  drush_print(dt('Renaming YAML Form to Webform...'));

  // Rename directories.
  $rename_directories = _drush_yamform_to_webform_get_rename_directories($webform_destination);
  foreach ($rename_directories as $source_directory) {
    _drush_yamform_to_webform_rename($source_directory);
  }

  // Rename files.
  $files = file_scan_directory($webform_destination, '/./');
  foreach ($files as $file) {
    _drush_yamform_to_webform_rename($file->uri);
  }

  // Search-n-replace file contents.
  $pre_str_replace_search = array_keys($config['replace']['pre_strings']);
  $pre_str_replace_replace = array_values($config['replace']['pre_strings']);
  $post_str_replace_search = array_keys($config['replace']['post_strings']);
  $post_str_replace_replace = array_values($config['replace']['post_strings']);

  $files = file_scan_directory($webform_destination, '/./');
  foreach ($files as $file_path => $file) {
    $contents = file_get_contents($file_path);
    if (stripos($contents, 'yamlform') === FALSE && stripos($contents, 'yaml form') === FALSE) {
      continue;
    }

    $module_path = str_replace("$webform_destination/", '', $file_path);

    // Pre replace.
    $contents = str_replace($pre_str_replace_search, $pre_str_replace_replace, $contents);
    if (isset($config['replace']['pre_paths'][$module_path])) {
      $contents = str_replace(
        array_keys($config['replace']['pre_paths'][$module_path]),
        $config['replace']['pre_paths'][$module_path],
        $contents
      );
    }

    // Convert all references to 'form' to 'webform'.
    // Note: This is risky but worth doing.
    $contents = preg_replace('/(["\'])form(s?)\1/', '\1webform\2\1', $contents);
    $contents = preg_replace('/(["\'])Form(s?)\1/', '\1Webform\2\1', $contents);
    $contents = preg_replace('/([ "\'>])form(s?)([ \.,\?<\'\:])/', '\1webform\2\3', $contents);
    $contents = preg_replace('/([ "\'>])Form(s?)([ \.,\?<\'\:])/', '\1Webform\2\3', $contents);

    // Post replace.
    $contents = str_replace($post_str_replace_search, $post_str_replace_replace, $contents);
    if (isset($config['replace']['post_paths'][$module_path])) {
      $contents = str_replace(
        array_keys($config['replace']['post_paths'][$module_path]),
        $config['replace']['post_paths'][$module_path],
        $contents
      );
    }

    // Custom cleanup.
    switch (basename($file_path)) {
      case 'webform_node.install':
        $lines = explode("\n", $contents);
        $lines = array_slice($lines, 0, 26);
        $contents = implode("\n", $lines);
    }

    if (drush_get_option(['verbose', 'debug'], FALSE)) {
      drush_print(dt("Search-n-replacing in '@path'...", [
        '@path' => str_replace(DRUPAL_ROOT . '/', '', $file_path),
      ]));
    }
    file_put_contents($file_path, $contents);
  }

  // Revert certain files that are now being customized for the Webform module.
  if (file_exists("$webform_destination/.git")) {
    drush_print(dt('Reverting selected files...'));
    foreach ($config['revert'] as $file) {
      `cd $webform_destination; git checkout -- $file`;
    }
  }
}

/**
 * Rename all file from YAML Form to Webform.
 *
 * @param string $source
 *   The file to be renamed.
 */
function _drush_yamform_to_webform_rename($source) {
  $destination_directory = dirname($source);
  $destination_name = str_replace(
    ['YamlForm', 'Yamlform', 'yamlform'],
    ['Webform', 'Webform', 'webform'],
    basename($source)
  );
  $destination = "$destination_directory/$destination_name";
  if ($source != $destination) {
    if (drush_get_option(['verbose', 'debug'], FALSE)) {
      drush_print(dt("Renaming '@source' to '@destination'...", [
        '@source' => str_replace(DRUPAL_ROOT . '/', '', $source),
        '@destination' => str_replace(DRUPAL_ROOT . '/', '', $destination),
      ]));
    }
    file_unmanaged_move($source, $destination);
  }
}

/**
 * Get directories to be renamed.
 *
 * @param string $source
 *   The webform module's directory.
 *
 * @return array
 *   An array of directories to be renamed sorted by deepest first.
 */
function _drush_yamform_to_webform_get_rename_directories($source) {
  $files = file_scan_directory($source, '/./');
  $directories = [];
  foreach ($files as $file) {
    $directory = dirname($file->uri);
    $directory = str_replace(DRUPAL_ROOT, '', $directory);
    while ($directory != '/' && stripos(basename($directory), 'yamlform') === FALSE) {
      $directory = dirname($directory);
    }
    if ($directory = ltrim($directory, '/')) {
      $directories[$directory] = $directory;
    }
  }
  ksort($directories);
  return array_reverse($directories);
}
