<?php

/**
 * @file
 * Post update functions for Group.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupContentType;
use Drupal\user\Entity\Role;

/**
 * Recalculate group type and group content type dependencies after moving the
 * plugin configuration from the former to the latter in group_update_8006().
 */
function group_post_update_group_type_group_content_type_dependencies() {
  foreach (GroupType::loadMultiple() as $group_type) {
    $group_type->save();
  }

  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Recalculate group content type dependencies after updating the group content
 * enabler base plugin dependency logic.
 */
function group_post_update_group_content_type_dependencies() {
  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Grant the new 'access group overview' permission.
 */
function group_post_update_grant_access_overview_permission() {
  /** @var \Drupal\user\RoleInterface $role */
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('administer group')) {
      $role->grantPermission('access group overview');
      $role->save();
    }
  }
}

/**
 * Fix cache contexts in views.
 */
function group_post_update_view_cache_contexts(&$sandbox) {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }
  // This will trigger the catch-all fix in group_view_presave().
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) {
    return TRUE;
  });
}
