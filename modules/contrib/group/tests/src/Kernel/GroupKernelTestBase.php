<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Defines an abstract test base for group kernel tests.
 */
abstract class GroupKernelTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Refactor tests to not automatically use group_test_config unless they
   *       have a good reason to.
   */
  public static $modules = ['group', 'group_test_config'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->pluginManager = $this->container->get('plugin.manager.group_content_enabler');

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
    $this->installConfig(['group', 'group_test_config']);

    $this->setCurrentUser($this->createUser());
  }

  /**
   * Sets the current user so group creation can rely on it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to set as the current user.
   */
  protected function setCurrentUser(AccountInterface $account) {
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Gets the current user so you can run some checks against them.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser() {
    return $this->container->get('current_user')->getAccount();
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group');
    $group = $storage->create($values + [
      'type' => 'default',
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group;
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group type entity.
   */
  protected function createGroupType(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group_type');
    $group_type = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_type);
    return $group_type;
  }

}
