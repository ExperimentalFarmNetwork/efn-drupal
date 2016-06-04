<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\Controller\AutoEntityLabelForm.
 */

namespace Drupal\auto_entitylabel\Form;

use Drupal\auto_entitylabel\AutoENtityLabelManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutoEntityLabelForm.
 *
 * @property \Drupal\Core\Config\ConfigFactoryInterface config_factory
 * @property \Drupal\Core\Entity\EntityManagerInterface entity_manager
 * @property  String entity_type_parameter
 * @property  String entity_type_id
 * @property \Drupal\auto_entitylabel\AutoENtityLabelManager auto_entity_label_manager
 * @package Drupal\auto_entitylabel\Controller
 */
class AutoEntityLabelForm extends ConfigFormBase {
  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $entitymanager;

  protected $route_match;

  /**
   * AutoEntityLabelController constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, RouteMatchInterface $route_match, AutoENtityLabelManager $auto_entity_label_manager) {
    parent::__construct($config_factory);
    $this->entitymanager = $entity_manager;
    $this->route_match = $route_match;
    $this->auto_entity_label_manager = $auto_entity_label_manager;
    $route_options = $this->route_match->getRouteObject()->getOptions();
    $this->entity_type_parameter = array_shift(array_keys($route_options['parameters']));
    $entity_type = $this->route_match->getParameter($this->entity_type_parameter);
    $this->entity_type_id = $entity_type->id();
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'auto_entitylabel.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'auto_entitylabel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('current_route_match'),
      $container->get('auto_entitylabel.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_parameter = $this->entity_type_parameter;
    $entity_type_id = $this->entity_type_id;

    $key = $entity_type_parameter . '_' . $entity_type_id;

    $config = $this->config('auto_entitylabel.settings');
    $form['auto_entitylabel'] = array(
      '#type' => 'fieldset',
      '#title' => t('Automatic label generation for @type', array('@type' => $entity_type_id)),
      '#weight' => 0,
    );

    $form['auto_entitylabel']['auto_entitylabel_' . $key] = array(
      '#type' => 'radios',
      '#default_value' => $config->get('auto_entitylabel_' . $key),
      '#options' => $this->auto_entity_label_manager->auto_entitylabel_options($entity_type_parameter, $entity_type_id),
    );

    $form['auto_entitylabel']['auto_entitylabel_pattern_' . $key] = array(
      '#type' => 'textarea',
      '#title' => t('Pattern for the title'),
      '#description' => t('Leave blank for using the per default generated title. Otherwise this string will be used as title. Use the syntax [token] if you want to insert a replacement pattern.'),
      '#default_value' => $config->get('auto_entitylabel_pattern_' . $key, ''),
    );

    // Don't allow editing of the pattern if PHP is used, but the users lacks
    // permission for PHP.
    if ($this->config('auto_entitylabel')->get('auto_entitylabel_php_' . $key) && !\Drupal::currentUser()->hasPermission('use PHP for label patterns')) {
      $form['auto_entitylabel']['auto_entitylabel_pattern_' . $key]['#disabled'] = TRUE;
      $form['auto_entitylabel']['auto_entitylabel_pattern_' . $key]['#description'] = t('You are not allow the configure the pattern for the title, as you lack the %permission permission.', array('%permission' => t('Use PHP for title patterns')));
    }

    // Display the list of available placeholders if token module is installed.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['auto_entitylabel']['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array($entity_type_parameter),
        '#dialog' => TRUE,
      );
    }

    $form['auto_entitylabel']['auto_entitylabel_php_' . $key] = array(
      '#access' => \Drupal::currentUser()->hasPermission('use PHP for label patterns'),
      '#type' => 'checkbox',
      '#title' => t('Evaluate PHP in pattern.'),
      '#description' => $this->t('Put PHP code above that returns your string, but make sure you surround code in <code>&lt;?php</code> and <code>?&gt;</code>. Note that <code>$entity</code> and <code>$language</code> are available and can be used by your code.'),
      '#default_value' => $config->get('auto_entitylabel_php_' . $key),
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $key = $this->entity_type_parameter . '_' . $this->entity_type_id;
    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('auto_entitylabel.settings');
    $config->set('auto_entitylabel_php_' . $key, $userInputValues['auto_entitylabel_php_' . $key]);
    $config->set('auto_entitylabel_pattern_' . $key, $userInputValues['auto_entitylabel_pattern_' . $key]);
    $config->set('auto_entitylabel_' . $key, $userInputValues['auto_entitylabel_' . $key]);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
