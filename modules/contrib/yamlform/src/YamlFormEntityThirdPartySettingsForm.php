<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure third party settings.
 */
class YamlFormEntityThirdPartySettingsForm extends EntityForm {

  /**
   * The third party settings manager.
   *
   * @var \Drupal\yamlform\YamlFormThirdPartySettingsManagerInterface
   */
  protected $settingsManager;

  /**
   * Constructs a new YamlFormEntityThirdPartySettingsForm.
   *
   * @param \Drupal\yamlform\YamlFormThirdPartySettingsManagerInterface $settings_manager
   *   The third party settings manager.
   */
  public function __construct(YamlFormThirdPartySettingsManagerInterface $settings_manager) {
    $this->settingsManager = $settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yamlform.third_party_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = $this->settingsManager->buildForm($form, $form_state);
    $form_state->set('yamlform', $this->getEntity());
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    // Don't display the delete button.
    unset($element['delete']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();
    $third_party_settings = $form_state->getValue('third_party_settings');
    foreach ($third_party_settings as $module => $third_party_setting) {
      foreach ($third_party_setting as $key => $value) {
        $yamlform->setThirdPartySetting($module, $key, $value);
      }
    }
    $yamlform->save();

    $this->logger('yamlform')->notice('Form settings @label saved.', ['@label' => $yamlform->label()]);
    drupal_set_message($this->t('Form settings %label saved.', ['%label' => $yamlform->label()]));
  }

}
