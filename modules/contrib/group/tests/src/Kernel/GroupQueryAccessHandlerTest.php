<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity\QueryAccess\Condition;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\group\Entity\Access\GroupQueryAccessHandler;

/**
 * Tests the behavior of group query access handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Access\GroupQueryAccessHandler
 * @group group
 */
class GroupQueryAccessHandlerTest extends GroupKernelTestBase {

  /**
   * The query access handler.
   *
   * @var \Drupal\group\Entity\Access\GroupQueryAccessHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $entity_type = $this->entityTypeManager->getDefinition('group');
    $this->handler = GroupQueryAccessHandler::createInstance($this->container, $entity_type);
  }

  /**
   * Tests that the the query is not altered for people who can bypass access.
   *
   * @covers ::getConditions
   */
  public function testBypassAccess() {
    $user = $this->createUser([], ['bypass group access']);
    foreach (['view', 'update', 'delete'] as $operation) {
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());
    }
  }

  /**
   * Tests that the query has no results for people without any access.
   *
   * @covers ::getConditions
   */
  public function testNoAccess() {
    $user = new AnonymousUserSession();
    foreach (['view', 'update', 'delete'] as $operation) {
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());
    }
  }

  /**
   * Tests the conditions for people with access in just the group type scope.
   *
   * @covers ::getConditions
   */
  public function testOnlyGroupTypeAccess() {
    $user = $this->createUser();

    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('type', ['default']),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    foreach (['update', 'delete'] as $operation) {
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());
    }
  }

  /**
   * Tests the conditions for people with access in just the group scope.
   *
   * @covers ::getConditions
   */
  public function testOnlyGroupAccess() {
    $user = $this->createUser();
    $group = $this->createGroup();
    $group->addMember($user);

    // Remove the 'view group' permission from the default group type's outsider
    // role so the user only has the permission for the group's they are in.
    $group_role = $this->entityTypeManager->getStorage('group_role')->load('default-outsider');
    $group_role->revokePermission('view group')->save();

    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('id', [$group->id()]),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    foreach (['update', 'delete'] as $operation) {
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());
    }
  }

  /**
   * Tests the conditions for people with access in both scopes.
   *
   * @covers ::getConditions
   */
  public function testCombinedAccess() {
    $user = $this->createUser();
    $group = $this->createGroup();
    $group->addMember($user);

    $conditions = $this->handler->getConditions('view', $user);
    $expected_sub_condition = new ConditionGroup();
    $expected_conditions = [
      $expected_sub_condition
        ->addCondition('type', ['default'])
        ->addCondition('id', [$group->id()], 'NOT IN'),
      new Condition('id', [$group->id()]),
    ];
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals('OR', $conditions->getConjunction());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    foreach (['update', 'delete'] as $operation) {
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.group_permissions', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());
    }
  }

}
