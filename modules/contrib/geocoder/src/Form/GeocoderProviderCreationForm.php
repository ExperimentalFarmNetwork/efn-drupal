<?php

declare(strict_types = 1);

namespace Drupal\geocoder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\geocoder\ProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple form that allows to select the provider type.
 *
 * This form is shown on the list of providers and leads to the full form to add
 * a new provider when submitted.
 */
class GeocoderProviderCreationForm extends FormBase {

  /**
   * The geocoder provider plugin manager.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a new GeocoderProviderCreationForm.
   *
   * @param \Drupal\geocoder\ProviderPluginManager $plugin_manager
   *   The geocoder provider plugin manager.
   */
  public function __construct(ProviderPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.geocoder.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'geocoder_provider_creation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $providers = [];
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      // @todo All provider plugins should implement this probably.
      // if (is_subclass_of($definition['class'], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $providers[$id] = $definition['name'];
      // }
    }
    asort($providers);

    $form['header']['#markup'] = '<h3>' . $this->t('Add a Geocoder provider') . '</h3>';

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
      '#open' => TRUE,
    ];

    $form['container']['geocoder_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Geocoder provider plugin'),
      '#title_display' => 'invisible',
      '#options' => $providers,
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['container']['actions'] = [
      '#type' => 'actions',
    ];

    $form['container']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    ];

    $providers = $this->getLinkGenerator()->generate(t('list of all available Geocoder providers'), Url::fromUri('https://packagist.org/providers/geocoder-php/provider-implementation', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));

    $form['help'] = [
      'caption' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('If the provider of your choice does not appear in the dropdown, make sure that it is installed using Composer. Here is the @providers_list.', [
          '@providers_list' => $providers,
        ]),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$form_state->getValue('geocoder_provider')) {
      $form_state->setErrorByName('geocoder_provider', $this->t('A Geocoder Provider to add should be selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('geocoder_provider')) {
      $form_state->setRedirect(
        'geocoder.geocoder_provider.admin_add',
        ['geocoder_provider_id' => $form_state->getValue('geocoder_provider')]
      );
    }
  }

}
