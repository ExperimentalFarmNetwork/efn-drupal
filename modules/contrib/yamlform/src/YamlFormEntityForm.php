<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for form.
 */
class YamlFormEntityForm extends BundleEntityFormBase {

  use YamlFormDialogTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Form element manager.
   *
   * @var \Drupal\yamlform\YamlFormElementManagerInterface
   */
  protected $elementManager;

  /**
   * Form element validator.
   *
   * @var \Drupal\yamlform\YamlFormEntityElementsValidator
   */
  protected $elementsValidator;

  /**
   * The token manager.
   *
   * @var \Drupal\yamlform\YamlFormTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a new YamlFormUiElementFormBase.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   * @param \Drupal\yamlform\YamlFormEntityElementsValidator $elements_validator
   *   Form element validator.
   * @param \Drupal\yamlform\YamlFormTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(RendererInterface $renderer, YamlFormElementManagerInterface $element_manager, YamlFormEntityElementsValidator $elements_validator, YamlFormTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
    $this->tokenManager = $token_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.yamlform.element'),
      $container->get('yamlform.elements_validator'),
      $container->get('yamlform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }

    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();

    // Customize title for duplicate form.
    if ($this->operation == 'duplicate') {
      // Display custom title.
      $form['#title'] = $this->t("Duplicate '@label' form", ['@label' => $yamlform->label()]);
      // If template, clear template's description and remove template flag.
      if ($yamlform->isTemplate()) {
        $yamlform->set('description', '');
        $yamlform->set('template', FALSE);
      }
    }

    $form = parent::buildForm($form, $form_state);
    $form = $this->buildDialog($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();

    // Only display id, title, and description for new forms.
    // Once a form is created this information is moved to the form's settings
    // tab.
    if ($yamlform->isNew()) {
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $yamlform->id(),
        '#machine_name' => [
          'exists' => '\Drupal\yamlform\Entity\YamlForm::load',
          'source' => ['title'],
        ],
        '#maxlength' => 32,
        '#disabled' => (bool) $yamlform->id() && $this->operation != 'duplicate',
        '#required' => TRUE,
      ];

      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#maxlength' => 255,
        '#default_value' => $yamlform->label(),
        '#required' => TRUE,
        '#id' => 'title',
        '#attributes' => [
          'autofocus' => 'autofocus',
        ],
      ];
      $form['description'] = [
        '#type' => 'yamlform_html_editor',
        '#title' => $this->t('Administrative description'),
        '#default_value' => $yamlform->get('description'),
      ];
      $form = $this->protectBundleIdElement($form);
    }

    // Call the isolated edit form that can be overridden by the
    // yamlform_ui.module.
    $form = $this->editForm($form, $form_state);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * Edit form element's source code form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function editForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();

    $t_args = [
      ':form_api_href' => 'https://www.drupal.org/node/37775',
      ':render_api_href' => 'https://www.drupal.org/developing/api/8/render',
      ':yaml_href' => 'https://en.wikipedia.org/wiki/YAML',
    ];
    $form['elements'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Elements (YAML)'),
      '#description' => $this->t('Enter a <a href=":form_api_href">Form API (FAPI)</a> and/or a <a href=":render_api_href">Render Array</a> as <a href=":yaml_href">YAML</a>.', $t_args) . '<br/>' .
        '<em>' . $this->t('Please note that comments are not supported and will be removed.') . '</em>',
      '#default_value' => $yamlform->get('elements') ,
      '#required' => TRUE,
    ];
    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate elements YAML.
    if ($messages = $this->elementsValidator->validate($this->getEntity())) {
      $form_state->setErrorByName('elements');
      foreach ($messages as $message) {
        drupal_set_message($message, 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($response = $this->validateDialog($form, $form_state)) {
      return $response;
    }

    parent::submitForm($form, $form_state);

    if ($this->isModalDialog()) {
      return $this->redirectForm($form, $form_state, Url::fromRoute('entity.yamlform.edit_form', ['yamlform' => $this->getEntity()->id()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();

    $is_new = $yamlform->isNew();
    $yamlform->save();

    if ($is_new) {
      $this->logger('yamlform')->notice('Form @label created.', ['@label' => $yamlform->label()]);
      drupal_set_message($this->t('Form %label created.', ['%label' => $yamlform->label()]));
    }
    else {
      $this->logger('yamlform')->notice('Form @label elements saved.', ['@label' => $yamlform->label()]);
      drupal_set_message($this->t('Form %label elements saved.', ['%label' => $yamlform->label()]));
    }
  }

}
