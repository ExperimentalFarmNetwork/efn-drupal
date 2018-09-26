<?php

namespace Drupal\yamlform_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\yamlform\Utility\YamlFormDialogHelper;
use Drupal\yamlform\YamlFormDialogTrait;
use Drupal\yamlform\YamlFormElementManagerInterface;
use Drupal\yamlform\YamlFormEntityElementsValidator;
use Drupal\yamlform\YamlFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for form element forms.
 *
 * The basic workflow for handling form elements.
 *
 * - Read the element.
 * - Build element's properties form.
 * - Set the property values.
 * - Alter the element's properties form.
 * - Process the element's properties form.
 * - Validate the element's properties form.
 * - Submit the element's properties form.
 * - Get property values from the form state's values.
 * - Remove default properties from the element's properties.
 * - Update element properties.
 */
abstract class YamlFormUiElementFormBase extends FormBase implements YamlFormUiElementFormInterface {

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
   * The form.
   *
   * @var \Drupal\yamlform\YamlFormInterface
   */
  protected $yamlform;

  /**
   * The form element.
   *
   * @var array
   */
  protected $element = [];

  /**
   * The form element's original element type.
   *
   * @var string
   */
  protected $originalType;

  /**
   * The action of the current form.
   *
   * @var string
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yamlform_ui_element_form';
  }

  /**
   * Constructs a new YamlFormUiElementFormBase.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   * @param \Drupal\yamlform\YamlFormEntityElementsValidator $elements_validator
   *   Form element validator.
   */
  public function __construct(RendererInterface $renderer, YamlFormElementManagerInterface $element_manager, YamlFormEntityElementsValidator $elements_validator) {
    $this->renderer = $renderer;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.yamlform.element'),
      $container->get('yamlform.elements_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, YamlFormInterface $yamlform = NULL, $key = NULL, $parent_key = '') {
    $this->yamlform = $yamlform;

    $yamlform_element = $this->getYamlFormElement();

    $form['properties'] = $yamlform_element->buildConfigurationForm([], $form_state);

    // Move messages to the top of the form.
    if (isset($form['properties']['messages'])) {
      $form['messages'] = $form['properties']['messages'];
      $form['messages']['#weight'] = -100;
      unset($form['properties']['messages']);
    }

    // Set parent key.
    $form['parent_key'] = [
      '#type' => 'value',
      '#value' => $parent_key,
    ];

    // Set element type.
    $form['properties']['element']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      'label' => [
        '#markup' => $yamlform_element->getPluginLabel(),
      ],
      '#weight' => -100,
      '#parents' => ['type'],
    ];

    // Set change element type.
    if ($key && $yamlform_element->getRelatedTypes($this->element)) {
      $route_parameters = ['yamlform' => $yamlform->id(), 'key' => $key];
      if ($this->originalType) {
        $original_yamlform_element = $this->elementManager->createInstance($this->originalType);
        $route_parameters = ['yamlform' => $yamlform->id(), 'key' => $key];
        $form['properties']['element']['type']['cancel'] = [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => new Url('entity.yamlform_ui.element.edit_form', $route_parameters),
          '#attributes' => YamlFormDialogHelper::getModalDialogAttributes(800, ['button', 'button--small']),
        ];
        $form['properties']['element']['type']['#description'] = '(' . $this->t('Changing from %type', ['%type' => $original_yamlform_element->getPluginLabel()]) . ')';
      }
      else {
        $form['properties']['element']['type']['change_type'] = [
          '#type' => 'link',
          '#title' => $this->t('Change'),
          '#url' => new Url('entity.yamlform_ui.change_element', $route_parameters),
          '#attributes' => YamlFormDialogHelper::getModalDialogAttributes(800, ['button', 'button--small']),
        ];
      }
    }

    // Set element key reserved word warning message.
    if (!$key) {
      $reserved_keys = ['form_build_id', 'form_token', 'form_id', 'data', 'op'];
      $reserved_keys = array_merge($reserved_keys, array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('yamlform_submission')));
      $form['#attached']['drupalSettings']['yamlform_ui']['reserved_keys'] = $reserved_keys;
      $form['#attached']['library'][] = 'yamlform_ui/yamlform_ui.element';
      $form['properties']['element']['key_warning'] = [
        '#type' => 'yamlform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t("Please avoid using the reserved word '@key' as the element's key."),
        '#weight' => -99,
        '#attributes' => ['style' => 'display:none'],
      ];
    }

    // Set element key.
    $form['properties']['element']['key'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Key'),
      '#machine_name' => [
        'label' => $this->t('Key'),
        'exists' => [$this, 'exists'],
        'source' => ['title'],
      ],
      '#required' => TRUE,
      '#parents' => ['key'],
      '#disabled' => ($key) ? TRUE : FALSE,
      '#default_value' => $key,
      '#weight' => -98,
    ];
    // Remove the key's help text (aka description) once it has been set.
    if ($key) {
      $form['properties']['element']['key']['#description'] = NULL;
    }
    // Use title for key (machine_name).
    if (isset($form['properties']['element']['title'])) {
      $form['properties']['element']['key']['#machine_name']['source'] = ['properties', 'element', 'title'];
      $form['properties']['element']['title']['#id'] = 'title';
    }

    // Set flex.
    // Hide #flex property if parent element is not a 'yamlform_flexbox'.
    if (isset($form['properties']['flex']) && !$this->isParentElementFlexbox($key, $parent_key)) {
      $form['properties']['flex']['#access'] = FALSE;
    }

    // Set actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#_validate_form' => TRUE,
    ];

    $form = $this->buildDialog($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only validate the submit button.
    $button = $form_state->getTriggeringElement();
    if (empty($button['#_validate_form'])) {
      return;
    }

    // The form element configuration is stored in the 'properties' key in
    // the form, pass that through for validation.
    $element_form_state = clone $form_state;
    $element_form_state->setValues($form_state->getValue('properties'));

    // Validate configuration form.
    $yamlform_element = $this->getYamlFormElement();
    $yamlform_element->validateConfigurationForm($form, $element_form_state);

    // Get errors for element validation.
    $element_errors = $element_form_state->getErrors();
    foreach ($element_errors as $element_error) {
      $form_state->setErrorByName(NULL, $element_error);
    }

    // Stop validation is the element properties has any errors.
    if ($form_state->hasAnyErrors()) {
      return;
    }

    // Set element properties.
    $properties = $yamlform_element->getConfigurationFormProperties($form, $element_form_state);
    $parent_key = $form_state->getValue('parent_key');
    $key = $form_state->getValue('key');
    if ($key) {
      $this->yamlform->setElementProperties($key, $properties, $parent_key);

      // Validate elements.
      if ($messages = $this->elementsValidator->validate($this->yamlform)) {
        $t_args = [':href' => Url::fromRoute('entity.yamlform.source_form', ['yamlform' => $this->yamlform->id()])->toString()];
        $form_state->setErrorByName('elements', $this->t('There has been error validating the elements. You may need to edit the <a href=":href">YAML source</a> to resolve the issue.', $t_args));
        foreach ($messages as $message) {
          drupal_set_message($message, 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $yamlform_element = $this->getYamlFormElement();

    if ($response = $this->validateDialog($form, $form_state)) {
      return $response;
    }

    // The form element configuration is stored in the 'properties' key in
    // the form, pass that through for submission.
    $element_form_state = clone $form_state;
    $element_form_state->setValues($form_state->getValue('properties'));

    // Submit element configuration.
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
    $yamlform_element->submitConfigurationForm($form, $element_form_state);

    // Save the form with its updated element.
    $this->yamlform->save();

    // Display status message.
    $properties = $form_state->getValue('properties');
    $t_args = [
      '%title' => (!empty($properties['title'])) ? $properties['title'] : $form_state->getValue('key'),
      '@action' => $this->action,
    ];
    drupal_set_message($this->t('%title has been @action.', $t_args));

    // Redirect.
    return $this->redirectForm($form, $form_state, $this->yamlform->toUrl('edit-form'));
  }

  /**
   * Determines if the form element key already exists.
   *
   * @param string $key
   *   The form element key.
   *
   * @return bool
   *   TRUE if the form element key, FALSE otherwise.
   */
  public function exists($key) {
    $elements = $this->yamlform->getElementsInitializedAndFlattened();
    return (isset($elements[$key])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return ($this instanceof YamlFormUiElementAddForm) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getYamlForm() {
    return $this->yamlform;
  }

  /**
   * {@inheritdoc}
   */
  public function getYamlFormElement() {
    return $this->elementManager->getElementInstance($this->element);
  }

  /**
   * Determine if the parent element is a 'yamlform_flexbox'.
   *
   * @param string|null $key
   *   The element's key. Only applicable for existing elements.
   * @param string|null $parent_key
   *   The element's parent key. Only applicable for new elements.
   *   Parent key is set via query string parameter. (?parent={parent_key})
   *
   * @return bool
   *   TRUE if the parent element is a 'yamlform_flexbox'.
   */
  protected function isParentElementFlexbox($key = NULL, $parent_key = NULL) {
    $elements = $this->yamlform->getElementsInitializedAndFlattened();

    // Check the element #yamlform_parent_flexbox property.
    if ($key && isset($elements[$key])) {
      return $elements[$key]['#yamlform_parent_flexbox'];
    }

    // Check the parent element #type.
    if ($parent_key && isset($elements[$parent_key]) && isset($elements[$parent_key]['#type'])) {
      return ($elements[$parent_key]['#type'] == 'yamlform_flexbox') ? TRUE : FALSE;
    }

    return FALSE;
  }

}
