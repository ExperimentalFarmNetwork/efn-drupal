<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the group permission calculator interface.
 *
 * Please make sure that when calculating permissions, you attach the right
 * cacheable metadata. This includes cache contexts if your implementation
 * causes the calculated permissions to vary by something.
 *
 * Do NOT use the user.group_permissions in any of the calculations as that
 * cache context is essentially a wrapper around the calculated permissions and
 * you'd therefore end up in an infinite loop.
 */
interface GroupPermissionCalculatorInterface {

  /**
   * Calculates the anonymous group permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the anonymous group permissions.
   */
  public function calculateAnonymousPermissions();

  /**
   * Calculates the outsider group permissions for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to calculate the outsider permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the outsider group permissions.
   */
  public function calculateOutsiderPermissions(AccountInterface $account);

  /**
   * Calculates the member group permissions for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to calculate the member permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the member group permissions.
   */
  public function calculateMemberPermissions(AccountInterface $account);

}
