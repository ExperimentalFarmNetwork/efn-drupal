<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\Group;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the way group content entities react to entity CRUD events.
 *
 * The entity_crud_hook_test module implements all core entity CRUD hooks and
 * stores a message for each in $GLOBALS['entity_crud_hook_test'].
 *
 * @group group
 */
class GroupContentCrudHookTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group', 'group_test_config', 'entity_crud_hook_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['group', 'group_test_config']);
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    // Required to be able to delete accounts. See User::postDelete().
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests that a grouped entity deletion triggers group content deletion.
   */
  public function testGroupedEntityDeletion() {
    $account = $this->createUser();
    Group::create([
      'type' => 'default',
      'uid' => $account->id(),
      'label' => $this->randomMachineName(),
    ])->save();

    // Start with a clean slate and delete the account.
    $GLOBALS['entity_crud_hook_test'] = [];
    $account->delete();

    $delete_user = 'entity_crud_hook_test_entity_delete called for type user';
    $position_1 = array_search($delete_user, $GLOBALS['entity_crud_hook_test']);
    $this->assertNotFalse($position_1, 'User delete hook fired.');

    $delete_gc = 'entity_crud_hook_test_entity_delete called for type group_content';
    $position_2 = array_search($delete_gc, $GLOBALS['entity_crud_hook_test']);
    $this->assertNotFalse($position_2, 'Group content delete hook fired.');
    $this->assertGreaterThan($position_2, $position_1, 'Group content delete hook fired after user delete hook.');

    $update_user = 'entity_crud_hook_test_entity_update called for type user';
    $position_3 = array_search($update_user, $GLOBALS['entity_crud_hook_test']);
    $this->assertFalse($position_3, 'User update hook not fired.');
  }

  /**
   * Tests that an ungrouped entity deletion triggers no group content deletion.
   */
  public function testUngroupedEntityDeletion() {
    $this->createUser()->delete();
    $delete_gc = 'entity_crud_hook_test_entity_delete called for type group_content';
    $this->assertFalse(array_search($delete_gc, $GLOBALS['entity_crud_hook_test']), 'Group content delete hook not fired.');
  }

}
