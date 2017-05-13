<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the behavior of group creators.
 *
 * @group group
 */
class GroupCreatorTest extends GroupKernelTestBase {

  /**
   * The account to use as the group creator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->account = $this->createUser();
  }

  /**
   * Tests that a group creator is automatically a member.
   */
  public function testCreatorMembership() {
    $group = $this->createGroup(['uid' => $this->account->id()]);

    $group_membership = $group->getMember($this->account);
    $this->assertNotFalse($group_membership, 'Membership could be loaded for the group creator.');

    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $group_roles = $group_role_storage->loadByUserAndGroup($this->account, $group);

    $this->assertCount(1, $group_roles, 'Membership has just one role.');
    $this->assertEquals('default-member', key($group_roles), 'Membership has the member role.');
  }

  /**
   * Tests that a group creator gets the configured roles.
   */
  public function testCreatorRoles() {
    /* @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $group_type->set('creator_roles', ['default-custom']);
    $group_type->save();

    $group = $this->createGroup(['uid' => $this->account->id()]);

    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $group_roles = $group_role_storage->loadByUserAndGroup($this->account, $group);
    ksort($group_roles);

    $this->assertCount(2, $group_roles, 'Membership has two roles.');

    $group_role = reset($group_roles);
    $this->assertEquals('default-custom', $group_role->id(), 'Membership has the custom role.');

    $group_role = next($group_roles);
    $this->assertEquals('default-member', $group_role->id(), 'Membership has the member role.');
  }

}
