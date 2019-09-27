<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Drupal\entity\QueryAccess\QueryAccessHandlerInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface as CGPII;
use Drupal\group\Access\GroupPermissionCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Controls query access for group entities.
 *
 * @see \Drupal\entity\QueryAccess\QueryAccessHandler
 */
class GroupQueryAccessHandler implements EntityHandlerInterface, QueryAccessHandlerInterface {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * Constructs a new QueryAccessHandlerBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   */
  public function __construct(EntityTypeInterface $entity_type, EventDispatcherInterface $event_dispatcher, AccountInterface $current_user, GroupPermissionCalculatorInterface $permission_calculator) {
    $this->entityType = $entity_type;
    $this->eventDispatcher = $event_dispatcher;
    $this->currentUser = $current_user;
    $this->groupPermissionCalculator = $permission_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('event_dispatcher'),
      $container->get('current_user'),
      $container->get('group_permission.chain_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions($operation, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $conditions = $this->buildConditions($operation, $account);

    // Allow other modules to modify the conditions before they are used.
    $event = new QueryAccessEvent($conditions, $operation, $account);
    $this->eventDispatcher->dispatch('entity.query_access.' . $this->entityType->id(), $event);

    return $conditions;
  }

  /**
   * Retrieves the group permission name for the given operation.
   *
   * @param string $operation
   *   The access operation. Usually one of "view", "update" or "delete".
   *
   * @return string
   *   The group permission name.
   */
  protected function getPermissionName($operation) {
    switch ($operation) {
      // @todo Could use the below if permission were named 'update group'.
      case 'update':
        $permission = 'edit group';
        break;

      case 'delete':
      case 'view':
        $permission = "$operation group";
        break;

      default:
        $permission = 'view group';
    }

    return $permission;
  }

  /**
   * Builds the conditions for the given operation and account.
   *
   * @param string $operation
   *   The access operation. Usually one of "view", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to restrict access.
   *
   * @return \Drupal\entity\QueryAccess\ConditionGroup
   *   The conditions.
   */
  protected function buildConditions($operation, AccountInterface $account) {
    $permission = $this->getPermissionName($operation);
    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.group_permissions']);

    // @todo Remove these lines once we kill the bypass permission.
    // If the account can bypass group access, we do not alter the query at all.
    $conditions->addCacheContexts(['user.permissions']);
    if ($account->hasPermission('bypass group access')) {
      return $conditions;
    }

    $calculated_permissions = $this->groupPermissionCalculator->calculatePermissions($account);
    $allowed_ids = $all_ids = [];
    foreach ($calculated_permissions->getItems() as $item) {
      $all_ids[$item->getScope()][] = $item->getIdentifier();
      if ($item->hasPermission($permission)) {
        $allowed_ids[$item->getScope()][] = $item->getIdentifier();
      }
    }

    // If no group type or group gave access, we deny access altogether.
    if (empty($allowed_ids[CGPII::SCOPE_GROUP_TYPE]) && empty($allowed_ids[CGPII::SCOPE_GROUP])) {
      $conditions->alwaysFalse();
      return $conditions;
    }

    // Add the allowed group types to the query (if any).
    if (!empty($allowed_ids[CGPII::SCOPE_GROUP_TYPE])) {
      $sub_condition = new ConditionGroup();
      $sub_condition->addCondition('type', $allowed_ids[CGPII::SCOPE_GROUP_TYPE]);

      // If the user had memberships, we need to make sure they are excluded
      // from group type based matches as the memberships' permissions take
      // precedence.
      if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
        $sub_condition->addCondition('id', $all_ids[CGPII::SCOPE_GROUP], 'NOT IN');
      }

      $conditions->addCondition($sub_condition);
    }

    // Add the memberships with access to the query (if any).
    if (!empty($allowed_ids[CGPII::SCOPE_GROUP])) {
      $conditions->addCondition('id', $allowed_ids[CGPII::SCOPE_GROUP]);
    }

    return $conditions;
  }

}
