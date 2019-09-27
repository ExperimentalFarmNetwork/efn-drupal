<?php

namespace Drupal\group\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Calculates group permissions for an account.
 */
class DefaultGroupPermissionCalculator extends GroupPermissionCalculatorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a DefaultGroupPermissionCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoaderInterface $membership_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAnonymousPermissions() {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();

    // @todo Introduce group_role_list:audience:anonymous cache tag.
    // If a new group type is introduced, we need to recalculate the anonymous
    // permissions hash. Therefore, we need to introduce the group type list
    // cache tag.
    $calculated_permissions->addCacheTags(['config:group_type_list']);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $storage = $this->entityTypeManager->getStorage('group_type');
    foreach ($storage->loadMultiple() as $group_type_id => $group_type) {
      $group_role = $group_type->getAnonymousRole();

      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE,
        $group_type_id,
        $group_role->getPermissions()
      );

      $calculated_permissions->addItem($item);
      $calculated_permissions->addCacheableDependency($group_role);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOutsiderPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();

    // @todo Introduce group_role_list:audience:outsider cache tag.
    // If a new group type is introduced, we need to recalculate the outsider
    // permissions. Therefore, we need to introduce the group type list cache
    // tag.
    $calculated_permissions->addCacheTags(['config:group_type_list']);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $storage = $this->entityTypeManager->getStorage('group_type');
    foreach ($storage->loadMultiple() as $group_type_id => $group_type) {
      $group_role = $group_type->getOutsiderRole();

      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE,
        $group_type_id,
        $group_role->getPermissions()
      );

      $calculated_permissions->addItem($item);
      $calculated_permissions->addCacheableDependency($group_role);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();

    // The member roles depend on which memberships you have, for which we do
    // not currently have a dedicated cache context as it has a very high
    // granularity. We therefore cache the calculated permissions per user.
    $calculated_permissions->addCacheContexts(['user']);

    // @todo Use a cache tag for memberships (e.g.: when new one is added).
    // If the user gets added to or removed from a group, their account will
    // be re-saved in GroupContent::postDelete() and GroupContent::postSave().
    // This means we can add the user's cacheable metadata to invalidate this
    // list of permissions whenever the user is saved.
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $calculated_permissions->addCacheableDependency($user);

    foreach ($this->membershipLoader->loadByUser($account) as $group_membership) {
      $group_id = $group_membership->getGroup()->id();
      $permission_sets = [];

      foreach ($group_membership->getRoles() as $group_role) {
        $permission_sets[] = $group_role->getPermissions();
        $calculated_permissions->addCacheableDependency($group_role);
      }

      $permissions = $permission_sets ? array_merge(...$permission_sets) : [];
      $item = new CalculatedGroupPermissionsItem(
        CalculatedGroupPermissionsItemInterface::SCOPE_GROUP,
        $group_id,
        $permissions
      );

      $calculated_permissions->addItem($item);
      $calculated_permissions->addCacheableDependency($group_membership);
    }

    return $calculated_permissions;
  }

}
