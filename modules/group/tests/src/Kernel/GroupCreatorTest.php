<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the behavior of group creators.
 *
 * @group group
 */
class GroupCreatorTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group', 'group_test_config'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->account = $this->createUser();

    $this->installConfig(['group', 'group_test_config']);
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
  }

  /**
   * Tests that a group creator is automatically a member.
   */
  public function testCreatorMembership() {
    /* @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->entityTypeManager->getStorage('group')->create([
      'type' => 'default',
      'uid' => $this->account->id(),
      'label' => $this->randomMachineName(),
    ]);
    $group->save();

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

    /* @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->entityTypeManager->getStorage('group')->create([
      'type' => 'default',
      'uid' => $this->account->id(),
      'label' => $this->randomMachineName(),
    ]);
    $group->save();

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
