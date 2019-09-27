<?php

namespace Drupal\ajax_comments\Form;

use Drupal\field_ui\FieldUI;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure 'ajax comments' settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a \Drupal\ajax_comments\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ajax_comments_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ajax_comments.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ajax_comments.settings');

    $field_list = $this->entityTypeManager
      ->getListBuilder('field_storage_config')->load();

    $form['entity_bundles'] = [
      '#type' => 'fieldset',
      '#title' => t("Enable Ajax Comments on the comment fields' display settings"),
      '#description' => t('These entity types and bundles have comment fields. You can enable or disable Ajax Comments on the field display settings. Click the links to visit the field display settings edit forms. Ajax Comments is enabled by default on all comment fields.'),
    ];

    $links = [];
    foreach ($field_list as $machine_name => $field_storage_config) {
      if ($field_storage_config->getType() === 'comment') {
        $entity_type_id = $field_storage_config->getTargetEntityTypeId();
        /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        // Load label info for the bundles of entity type $entity_type_id.
        $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        // Load a list of bundles that use the current field configuration.
        $bundles = $field_storage_config->getBundles();

        foreach ($bundles as $bundle) {
          // Create the link label.
          $bundle_label = $bundle_info[$bundle]['label'];
          $entity_type_label = $entity_type->getLabel()->render();
          $label = $entity_type_label . ': ' . $bundle_label;

          // Create the render array for the link to edit the display mode.
          $links[$entity_type_id . '.' . $bundle] = [
            '#type' => 'link',
            '#title' => $label,
            '#url' => Url::fromRoute(
              'entity.entity_view_display.' . $entity_type_id . '.default',
              FieldUI::getRouteBundleParameter($entity_type, $bundle)
            ),
          ];
        }
      }
    }
    $form['entity_bundles']['links'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $links,
    ];

    $form['notify'] = [
      '#title' => $this->t('Add notification message when comment posted'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('notify'),
    ];

    $form['enable_scroll'] = [
      '#title' => $this->t('Enable scrolling events'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_scroll'),
    ];

    $form['reply_autoclose'] = [
      '#title' => t('Autoclose any opened reply forms'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('reply_autoclose'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ajax_comments.settings')
      ->set('notify', $form_state->getValue('notify'))
      ->set('enable_scroll', $form_state->getValue('enable_scroll'))
      ->set('reply_autoclose', $form_state->getValue('reply_autoclose'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
