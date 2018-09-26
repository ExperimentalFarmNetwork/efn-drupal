<?php

namespace Drupal\yamlform\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\YamlFormTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Form' block.
 *
 * @Block(
 *   id = "yamlform_block",
 *   admin_label = @Translation("Form"),
 *   category = @Translation("Form")
 * )
 */
class YamlFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token manager.
   *
   * @var \Drupal\yamlform\YamlFormTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\yamlform\YamlFormTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, YamlFormTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('yamlform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'yamlform_id' => '',
      'default_data' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['yamlform_id'] = [
      '#title' => $this->t('Form'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'yamlform',
      '#required' => TRUE,
      '#default_value' => $this->getYamlForm(),
    ];
    $form['default_data'] = [
      '#title' => $this->t('Default form submission data (YAML)'),
      '#description' => $this->t('Enter form submission data as name and value pairs which will be used to prepopulate the selected form. You may use tokens.'),
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#default_value' => $this->configuration['default_data'],
    ];
    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['yamlform_id'] = $form_state->getValue('yamlform_id');
    $this->configuration['default_data'] = $form_state->getValue('default_data');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = ['data' => $this->configuration['default_data']];
    return $this->getYamlForm()->getSubmissionForm($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $yamlform = $this->getYamlForm();
    if (!$yamlform || !$yamlform->access('submission_create', $account)) {
      return AccessResult::forbidden();
    }
    else {
      return parent::blockAccess($account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Caching strategy is handled by the form.
    return 0;
  }

  /**
   * Get this block instance form.
   *
   * @return \Drupal\yamlform\YamlFormInterface
   *   A form or NULL.
   */
  protected function getYamlForm() {
    return YamlForm::load($this->configuration['yamlform_id']);
  }

}
