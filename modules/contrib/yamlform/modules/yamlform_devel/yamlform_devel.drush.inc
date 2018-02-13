<?php

/**
 * @file
 * YAML Form module drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function yamlform_devel_drush_command() {
  $items = [];

  $items['yamlform-devel-reset'] = [
    'description' => 'Resets YAML Form user data and saved state for messages',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'yamlform-repair' => 'Resets YAML Form user data and saved state for messages',
    ],
    'aliases' => ['yamlform-reset'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function yamlform_devel_drush_help($section) {
  switch ($section) {
    case 'drush:yamlform-reset':
      return dt('Resets YAML Form user data and saved state for messages');

    case 'meta:yamlform:title':
      return dt('YAML Form development commands');

    case 'meta:yamlform:summary':
      return dt('Developer specific commands for the YAML Form module.');
  }
}

/******************************************************************************/
// Repair.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_yamlform_devel_reset() {
  if (!drush_confirm(dt("Are you sure you want reset the YAML Form module's user data and saved state?"))) {
    return drush_user_abort();
  }

  drush_print('Resetting message closed via State API...');
  \Drupal::state()->delete('yamlform.element.message');

  drush_print('Resetting message closed via User Data...');
  \Drupal::service('user.data')->delete('yamlform', NULL, 'yamlform.element.message');
}
