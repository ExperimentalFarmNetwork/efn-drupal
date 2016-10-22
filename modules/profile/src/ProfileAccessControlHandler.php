<?php

namespace Drupal\profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\ProfileType;

/**
 * Defines the access control handler for the profile entity type.
 *
 * @see \Drupal\profile\Entity\Profile
 */
class ProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    if ($account->hasPermission("bypass {$this->entityTypeId} access")) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    if ($account->hasPermission("bypass {$this->entityTypeId} access")) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   *
   * When the $operation is 'add' then the $entity is of type 'profile_type',
   * otherwise $entity is of type 'profile'.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $account = $this->prepareUser($account);

    if ($account->hasPermission("bypass {$this->entityTypeId} access")) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $result;
    }

    $own_permission = "$operation own {$entity->bundle()} {$entity->getEntityTypeId()}";
    $any_permission = "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}";

    // Some times, operation edit is called update.
    // Use edit in any case.
    if ($operation == 'update') {
      $operation = 'edit';
    }
    elseif ($operation == 'create') {
      $operation = 'add';
    }

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      if (($account->id() == $entity->getOwnerId())) {
        $result = AccessResult::allowedIfHasPermission($account, $own_permission);
        if ($result->isNeutral()) {
          // Check if user has "any" permission, still.
          // Users may provide only "any" permission not just "own".
          $result = AccessResult::allowedIfHasPermission($account, $any_permission);
        }
      }
      else {
        $result = AccessResult::allowedIfHasPermission($account, $any_permission);
      }
    }

    // If access is allowed, check role restriction.
    if ($result->isAllowed()) {
      $bundle = ProfileType::load($entity->bundle());
      if (!empty(array_filter($bundle->getRoles()))) {
        $result = AccessResult::allowedIf(!empty(array_intersect($account->getRoles(), $bundle->getRoles())));
      }
    }

    $result->cachePerUser()->addCacheableDependency($entity);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);

    if ($result->isNeutral()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'add any ' . $entity_bundle . ' ' . $this->entityTypeId,
        'add own ' . $entity_bundle . ' ' . $this->entityTypeId,
      ], 'OR');
    }

    // If access is allowed, check role restriction.
    if ($result->isAllowed()) {
      $bundle = ProfileType::load($entity_bundle);
      if (!empty(array_filter($bundle->getRoles()))) {
        $result = AccessResult::allowedIf(!empty(array_intersect($account->getRoles(), $bundle->getRoles())));
      }
    }

    return $result;
  }

}
