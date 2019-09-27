<?php

namespace Drupal\group;

use Drupal\User\RoleInterface;

/**
 * Provides information about how site roles are synchronized to group roles.
 */
interface GroupRoleSynchronizerInterface {

  /**
   * Generates an ID for a synchronized group role.
   *
   * @param $group_type_id
   *   The ID of the group type the group role ID should be generated for.
   * @param $role_id
   *   The ID of the user role the group role ID should be generated for.
   *
   * @return string
   *   The group role ID for the given group type and user role.
   */
  public function getGroupRoleId($group_type_id, $role_id);

  /**
   * Retrieves all synchronized group role IDs for a group type.
   *
   * @param string $group_type_id
   *   The ID of the group type the group role IDs should be retrieved for.
   *
   * @return string[]
   *   The synchronized group role IDs for the given group type.
   */
  public function getGroupRoleIdsByGroupType($group_type_id);

  /**
   * Retrieves all synchronized group role IDs for a list of group types.
   *
   * @param string[] $group_type_ids
   *   The IDs of the group types the group role IDs should be retrieved for.
   *
   * @return string[]
   *   The synchronized group role IDs for the given group type.
   */
  public function getGroupRoleIdsByGroupTypes($group_type_ids);

  /**
   * Retrieves all synchronized group role IDs for a user role.
   *
   * @param string $role_id
   *   The ID of the user role the group role IDs should be retrieved for.
   *
   * @return string[]
   *   The synchronized group role IDs for the given user role.
   */
  public function getGroupRoleIdsByUserRole($role_id);

  /**
   * Retrieves all synchronized group role IDs for a list of user roles.
   *
   * @param string[] $role_ids
   *   The IDs of the user roles the group role IDs should be retrieved for.
   *
   * @return string[]
   *   The synchronized group role IDs for the given user role.
   */
  public function getGroupRoleIdsByUserRoles($role_ids);

  /**
   * Creates group roles for all user roles.
   *
   * @param string[] $group_type_ids
   *   (optional) A list of group type IDs to synchronize roles for. Leave empty
   *   to synchronize roles for all group types.
   * @param string[] $role_ids
   *   (optional) A list of user role IDs to synchronize. Leave empty to
   *   synchronize all user roles.
   *
   * @deprecated in Group 1.0-rc3, will be removed before Group 1.0. Use
   *   \Drupal\group\Entity\Storage\GroupRoleStorage::createSynchronized()
   *   instead.
   */
  public function createGroupRoles($group_type_ids = NULL, $role_ids = NULL);

  /**
   * Updates the label of all group roles for a user role.
   *
   * @param \Drupal\User\RoleInterface $role
   *   The user role to update the group role labels for.
   *
   * @deprecated in Group 1.0-rc3, will be removed before Group 1.0. Use
   *   \Drupal\group\Entity\Storage\GroupRoleStorage::updateSynchronizedLabels()
   *   instead.
   */
  public function updateGroupRoleLabels(RoleInterface $role);

}
