<?php

namespace Drupal\group\Plugin;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base class for GroupContentEnabler plugins.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see plugin_api
 */
abstract class GroupContentEnablerBase extends PluginBase implements GroupContentEnablerInterface {

  /**
   * The ID of group type this plugin was instantiated for.
   *
   * @var string
   */
  protected $groupTypeId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Only support setting the group type ID during construction.
    if (!empty($configuration['group_type_id'])) {
      $this->groupTypeId = $configuration['group_type_id'];
    }

    // Include the default configuration by calling ::setConfiguration().
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle() {
    return $this->pluginDefinition['entity_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupCardinality() {
    return $this->configuration['group_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCardinality() {
    return $this->configuration['entity_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    if ($id = $this->getGroupTypeId()) {
      return GroupType::load($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->groupTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnforced() {
    return $this->pluginDefinition['enforced'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLabel(GroupContentInterface $group_content) {
    return $group_content->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfigId() {
    $preferred_id = $this->getGroupTypeId() . '-' . str_replace(':', '-', $this->getPluginId());

    // Return a hashed ID if the readable ID would exceed the maximum length.
    if (strlen($preferred_id) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      $hashed_id = 'group_content_type_' . md5($preferred_id);
      $preferred_id = substr($hashed_id, 0, EntityTypeInterface::BUNDLE_MAX_LENGTH);
    }

    return $preferred_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeLabel() {
    return $this->getGroupType()->label() . ': ' . $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeDescription() {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $plugin_id = $this->getPluginId();
    $defaults = [
      'title_args' => ['%plugin_name' => $this->getLabel()],
    ];

    $permissions["view $plugin_id content"] = [
      'title' => '%plugin_name: View content',
    ] + $defaults;

    $permissions["create $plugin_id content"] = [
      'title' => '%plugin_name: Create new content',
    ] + $defaults;

    $permissions["edit own $plugin_id content"] = [
      'title' => '%plugin_name: Edit own content',
    ] + $defaults;

    $permissions["edit any $plugin_id content"] = [
      'title' => '%plugin_name: Edit any content',
    ] + $defaults;

    $permissions["delete own $plugin_id content"] = [
      'title' => '%plugin_name: Delete own content',
    ] + $defaults;

    $permissions["delete any $plugin_id content"] = [
      'title' => '%plugin_name: Delete any content',
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    $plugin_id = $this->getPluginId();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "create $plugin_id content");
  }

  /**
   * Performs access check for the view operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function viewAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $plugin_id = $this->getPluginId();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "view $plugin_id content");
  }

  /**
   * Performs access check for the update operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $plugin_id = $this->getPluginId();

    // Allow members to edit their own group content.
    if ($group_content->getOwnerId() == $account->id()) {
      return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "edit own $plugin_id content");
    }

    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "edit any $plugin_id content");
  }

  /**
   * Performs access check for the delete operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $plugin_id = $this->getPluginId();

    // Allow members to delete their own group content.
    if ($group_content->getOwnerId() == $account->id()) {
      return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "delete own $plugin_id content");
    }

    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "delete any $plugin_id content");
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(GroupContentInterface $group_content, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $result = $this->viewAccess($group_content, $account);
        break;
      case 'update':
        $result = $this->updateAccess($group_content, $account);
        break;
      case 'delete':
        $result = $this->deleteAccess($group_content, $account);
        break;
      default:
        $result = GroupAccessResult::neutral();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    $settings['target_type'] = $this->getEntityTypeId();
    if ($bundle = $this->getEntityBundle()) {
      $settings['handler_settings']['target_bundles'] = [$bundle];
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Do not allow the changing of the group type ID after construction.
    unset($configuration['group_type_id']);

    // Merge in the default configuration.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Warning: For every key defined here you need to have a matching config
    // schema entry following the pattern group_content_enabler.config.MY_KEY!
    // @see group.schema.yml
    return [
      'group_cardinality' => 0,
      'entity_cardinality' => 0,
      'info_text' => [
        // This string will be saved as part of the group type config entity. We
        // do not use a t() function here as it needs to be stored untranslated.
        'value' => '<p>Please fill out any available fields to describe the relation between the content and the group.</p>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $replace = [
      '%entity_type' => $entity_type_manager->getDefinition($this->getEntityTypeId())->getLabel(),
      '%group_type' => $this->getGroupType()->label(),
      '%plugin' => $this->getLabel(),
    ];

    $form['group_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Group cardinality'),
      '#description' => $this->t('The amount of %group_type groups a single %entity_type entity can be added to as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['group_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['entity_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Entity cardinality'),
      '#description' => $this->t('The amount of times a single %entity_type entity can be added to the same %group_type group as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['entity_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['info_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Informational text'),
      '#description' => $this->t('A bit of info to show atop every form that links a %entity_type entity to a %group_type group.', $replace),
      '#default_value' => $this->configuration['info_text']['value'],
    ];

    // Only specify a default format if the data has been saved before.
    if (!empty($this->configuration['info_text']['format'])) {
      $form['info_text']['#format'] = $this->configuration['info_text']['format'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Only override this function if you need to do something specific to the
   * submitted data before it is saved as configuration on the plugin. The data
   * gets saved on the plugin in \Drupal\group\Entity\Form\GroupContentTypeForm.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
