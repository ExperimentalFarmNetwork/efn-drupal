<?php

namespace Drupal\yamlform;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\yamlform\Element\YamlFormSelectOther;
use Drupal\yamlform\Entity\YamlFormOptions;
use Drupal\yamlform\Utility\YamlFormArrayHelper;
use Drupal\yamlform\Utility\YamlFormElementHelper;
use Drupal\yamlform\Utility\YamlFormReflectionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a form element.
 *
 * @see \Drupal\yamlform\YamlFormElementInterface
 * @see \Drupal\yamlform\YamlFormElementManager
 * @see \Drupal\yamlform\YamlFormElementManagerInterface
 * @see plugin_api
 */
class YamlFormElementBase extends PluginBase implements YamlFormElementInterface {

  use StringTranslationTrait;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The element's properties without the initial hash (#) character.
   *
   * @var array
   */
  protected $properties;

  /**
   * The form element manager.
   *
   * @var \Drupal\yamlform\YamlFormElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, YamlFormElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('yamlform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.yamlform.element')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Only a few elements don't inherit these default properties.
   *
   * @see \Drupal\yamlform\Plugin\YamlFormElement\Textarea
   * @see \Drupal\yamlform\Plugin\YamlFormElement\YamlFormLikert
   * @see \Drupal\yamlform\Plugin\YamlFormElement\YamlFormCompositeBase
   * @see \Drupal\yamlform\Plugin\YamlFormElement\ContainerBase
   */
  public function getDefaultProperties() {
    return [
      // Element settings.
      'title' => '',
      'description' => '',
      'default_value' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      // Submission display.
      'format' => $this->getDefaultFormat(),
      // Custom attributes.
      'wrapper_attributes__class' => '',
      'wrapper_attributes__style' => '',
      'attributes__class' => '',
      'attributes__style' => '',
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * Get default base properties used by all elements.
   *
   * @return array
   *   An associative array containing base properties used by all elements.
   */
  protected function getDefaultBaseProperties() {
    return [
      // Administration.
      'admin_title' => '',
      'private' => FALSE,
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      // Element access.
      'access_create_roles' => ['anonymous', 'authenticated'],
      'access_create_users' => [],
      'access_update_roles' => ['anonymous', 'authenticated'],
      'access_update_users' => [],
      'access_view_roles' => ['anonymous', 'authenticated'],
      'access_view_users' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return [
      'title',
      'description',
      'field_prefix',
      'field_suffix',
      'required_error',
      'admin_title',
      'placeholder',
      'markup',
      'test',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return isset($default_properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginApiUrl() {
    return (!empty($this->pluginDefinition['api'])) ? Url::fromUri($this->pluginDefinition['api']) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginApiLink() {
    $api_url = $this->getPluginApiUrl();
    return ($api_url) ? Link::fromTextAndUrl($this->getPluginLabel(), $api_url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return str_replace('yamlform_', '', $this->pluginDefinition['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return (!empty($element['#type'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWrapper(array $element) {
    return $this->hasProperty('wrapper_attributes__class');
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return ($this->isInput($element)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    return $this->pluginDefinition['multiline'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return $this->pluginDefinition['multiple'];
  }

  /**
   * {@inheritdoc}
   */
  public function isComposite() {
    return $this->pluginDefinition['composite'];
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return $this->pluginDefinition['hidden'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return \Drupal::config('yamlform.settings')->get('elements.excluded_types.' . $this->pluginDefinition['id']) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $this->elementInfo->getInfo($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    $types = [];

    $parent_classes = YamlFormReflectionHelper::getParentClasses($this, 'YamlFormElementBase');

    $plugin_id = $this->getPluginId();
    $is_container = $this->isContainer($element);
    $has_multiple_values = $this->hasMultipleValues($element);
    $is_multiline = $this->isMultiline($element);

    $elements = $this->elementManager->getInstances();
    foreach ($elements as $element_name => $element_instance) {
      // Skip self.
      if ($plugin_id == $element_instance->getPluginId()) {
        continue;
      }

      // Skip disable or hidden.
      if (!$element_instance->isEnabled() || $element_instance->isHidden()) {
        continue;
      }

      // Compare element base (abstract) class.
      $element_instance_parent_classes = YamlFormReflectionHelper::getParentClasses($element_instance, 'YamlFormElementBase');
      if ($parent_classes[1] != $element_instance_parent_classes[1]) {
        continue;
      }

      // Compare container, multiple values, and multiline.
      if ($is_container != $element_instance->isContainer($element)) {
        continue;
      }
      if ($has_multiple_values != $element_instance->hasMultipleValues($element)) {
        continue;
      }
      if ($is_multiline != $element_instance->isMultiline($element)) {
        continue;
      }

      $types[$element_name] = $element_instance->getPluginLabel();
    }

    asort($types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set element options.
    if (isset($element['#options'])) {
      $element['#options'] = YamlFormOptions::getElementOptions($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    $attributes_property = ($this->hasWrapper($element)) ? '#wrapper_attributes' : '#attributes';

    // Check is the element is disabled and hide it.
    if ($this->isDisabled()) {
      if ($yamlform_submission->getYamlForm()->access('edit')) {
        $this->displayDisabledWarning($element);
      }
      $element['#access'] = FALSE;
    }

    // Apply element specific access rules.
    $operation = ($yamlform_submission->isCompleted()) ? 'update' : 'create';
    $element['#access'] = $this->checkAccessRules($operation, $element);

    // Add #allowed_tags.
    $allowed_tags = $this->configFactory->get('yamlform.settings')->get('elements.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        $element['#allowed_tags'] = Xss::getAdminTagList();
        break;

      case 'html':
        $element['#allowed_tags'] = Xss::getHtmlTagList();
        break;

      default:
        $element['#allowed_tags'] = preg_split('/ +/', $allowed_tags);
        break;
    }

    // Add inline title display support.
    if (isset($element['#title_display']) && $element['#title_display'] == 'inline') {
      unset($element['#title_display']);
      $element['#wrapper_attributes']['class'][] = 'yamlform-element--title-inline';
    }

    // Add default description display.
    $default_description_display = $this->configFactory->get('yamlform.settings')->get('elements.default_description_display');
    if ($default_description_display && !isset($element['#description_display']) && $this->hasProperty('description_display')) {
      $element['#description_display'] = $default_description_display;
    }

    // Add tooltip description display support.
    if (isset($element['#description_display']) && $element['#description_display'] === 'tooltip') {
      $element['#description_display'] = 'invisible';
      $element[$attributes_property]['class'][] = 'js-yamlform-element-tooltip';
      $element[$attributes_property]['class'][] = 'yamlform-element-tooltip';
      $element['#attached']['library'][] = 'yamlform/yamlform.element.tooltip';
    }

    // Add .yamlform-has-field-prefix and .yamlform-has-field-suffix class.
    if (!empty($element['#field_prefix'])) {
      $element[$attributes_property]['class'][] = 'yamlform-has-field-prefix';
    }
    if (!empty($element['#field_suffix'])) {
      $element[$attributes_property]['class'][] = 'yamlform-has-field-suffix';
    }

    // Add validation handler for #unique value.
    if (!empty($element['#unique']) && !$this->hasMultipleValues($element)) {
      $element['#element_validate'][] = [get_class($this), 'validateUnique'];
      $element['#yamlform'] = $yamlform_submission->getYamlForm()->id();
      $element['#yamlform_submission'] = $yamlform_submission->id();
    }

    // Prepare Flexbox and #states wrapper.
    $this->prepareWrapper($element);

    // Replace tokens for all properties.
    $token_data = [
      'yamlform' => $yamlform_submission->getYamlForm(),
      'yamlform-submission' => $yamlform_submission,
    ];
    $token_options = ['clear' => TRUE];
    YamlFormElementHelper::replaceTokens($element, $token_data, $token_options);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, array $element, AccountInterface $account = NULL) {
    // Respect elements that already have their #access set to FALSE.
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return FALSE;
    }

    if (!$account) {
      $account = $this->currentUser;
    }

    if (!empty($element['#access_' . $operation . '_roles']) && !array_intersect($element['#access_' . $operation . '_roles'], $account->getRoles())) {
      return FALSE;
    }
    elseif (!empty($element['#access_' . $operation . '_users']) && !in_array($account->id(), $element['#access_' . $operation . '_users'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Set an elements Flexbox and #states wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareWrapper(array &$element) {
    // Fix #states wrapper.
    if ($this->pluginDefinition['states_wrapper']) {
      YamlFormElementHelper::fixStatesWrapper($element);
    }

    // Add flex(box) wrapper.
    if (!empty($element['#yamlform_parent_flexbox'])) {
      $flex = (isset($element['#flex'])) ? $element['#flex'] : 1;
      $element += ['#prefix' => '', '#suffix' => ''];
      $element['#prefix'] = '<div class="yamlform-flex yamlform-flex--' . $flex . '"><div class="yamlform-flex--container">' . $element['#prefix'];
      $element['#suffix'] = $element['#suffix'] . '</div></div>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    $t_args = [
      '%title' => $this->getLabel($element),
      '%type' => $this->getPluginLabel(),
      ':href' => Url::fromRoute('yamlform.settings')->setOption('fragment', 'edit-elements')->toString(),
    ];
    if ($this->currentUser->hasPermission('administer yamlform')) {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Go to the <a href=":href">admin settings</a> page to enable this element.', $t_args);
    }
    else {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Please contact a site administrator.', $t_args);
    }
    drupal_set_message($message, 'warning');

    $context = [
      '@title' => $this->getLabel($element),
      '@type' => $this->getPluginLabel(),
      'link' => Link::fromTextAndUrl(t('Edit'), Url::fromRoute('<current>'))
        ->toString(),
    ];
    $this->logger->notice("'@title' is a '@type' element, which has been disabled and will not be rendered.", $context);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {}

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $element) {
    return $element['#title'] ?: $element['#yamlform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel(array $element) {
    return $element['#admin_title'] ?: $element['#title'] ?: $element['#yamlform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(array $element) {
    return $element['#yamlform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array &$element, $value, array $options = []) {
    return $this->build('html', $element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array &$element, $value, array $options = []) {
    return $this->build('text', $element, $value, $options);
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, $value, array $options = []) {
    $options['multiline'] = $this->isMultiline($element);
    $format_function = 'format' . ucfirst($format);
    $formatted_value = $this->$format_function($element, $value, $options);

    // Return NULL for empty formatted value.
    if ($formatted_value === '') {
      return NULL;
    }

    // Convert string to renderable #markup.
    if (is_string($formatted_value)) {
      $formatted_value = ['#markup' => $formatted_value];
    }

    return [
      '#theme' => 'yamlform_element_base_' . $format,
      '#element' => $element,
      '#value' => $formatted_value,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array &$element, $value, array $options = []) {
    return $this->formatText($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array &$element, $value, array $options = []) {
    // Return empty value.
    if ($value === '' || $value === NULL) {
      return '';
    }

    // Flatten arrays. Generally, $value is a string.
    if (is_array($value) && count($value) == count($value, COUNT_RECURSIVE)) {
      $value = implode(', ', $value);
    }

    // Apply XSS filter to value that contains HTML tags and is not formatted as
    // raw.
    $format = $this->getFormat($element);
    if ($format != 'raw' && is_string($value) && strpos($value, '<') !== FALSE) {
      $value = Xss::filter($value);
    }

    // Format value based on the element type using default settings.
    if (isset($element['#type'])) {
      // Apply #field prefix and #field_suffix to value.
      if (isset($element['#field_prefix'])) {
        $value = $element['#field_prefix'] . $value;
      }
      if (isset($element['#field_suffix'])) {
        $value .= $element['#field_suffix'];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValue(array $element, YamlFormInterface $yamlform) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    return [
      'value' => $this->t('Value'),
      'raw' => $this->t('Raw value'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat(array $element) {
    if (isset($element['#format'])) {
      return $element['#format'];
    }
    elseif ($default_format = $this->configFactory->get('yamlform.settings')->get('format.' . $this->getPluginId())) {
      return $default_format;
    }
    else {
      return $this->getDefaultFormat();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#yamlform_key'];
    return [
      'element__' . $key => [
        'title' => $this->getAdminLabel($element),
        'sort' => TRUE,
        'key' => $key,
        'property_name' => NULL,
        'element' => $element,
        'plugin' => $this,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, $value, array $options = []) {
    return $this->formatHtml($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $export_options) {
    if ($export_options['header_format'] == 'label') {
      return [$this->getAdminLabel($element)];
    }
    else {
      return [$element['#yamlform_key']];
    }
  }

  /**
   * Prefix an element's export header.
   *
   * @param array $header
   *   An element's export header.
   * @param array $element
   *   An element.
   * @param array $export_options
   *   An associative array of export options.
   *
   * @return array
   *   An element's export header with prefix.
   */
  protected function prefixExportHeader(array $header, array $element, array $export_options) {
    if (empty($export_options['header_prefix'])) {
      return $header;
    }

    if ($export_options['header_format'] == 'label') {
      $prefix = $this->getAdminLabel($element) . $export_options['header_prefix_label_delimiter'];
    }
    else {
      $prefix = $this->getKey($element) . $export_options['header_prefix_key_delimiter'];;
    }

    foreach ($header as $index => $column) {
      $header[$index] = $prefix . $column;
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, $value, array $export_options) {
    $element['#format'] = 'raw';
    return [$this->formatText($element, $value, $export_options)];
  }

  /**
   * Form API callback. Validate #unique value.
   */
  public static function validateUnique(array &$element, FormStateInterface $form_state) {
    $yamlform_id = $element['#yamlform'];
    $sid = $element['#yamlform_submission'];
    $name = $element['#name'];
    $value = $element['#value'];

    // Skip empty unique fields.
    if ($value == '') {
      return;
    }

    // Using range() is more efficient than using countQuery() for data checks.
    $query = Database::getConnection()->select('yamlform_submission_data')
      ->fields('yamlform_submission_data', ['sid'])
      ->condition('yamlform_id', $yamlform_id)
      ->condition('name', $name)
      ->condition('value', $value)
      ->range(0, 1);
    if ($sid) {
      $query->condition('sid', $sid, '<>');
    }
    $count = $query->execute()->fetchField();
    if ($count) {
      $form_state->setError($element, t('The value %value has already been submitted once for the %title field. You may have already submitted this form, or you need to use a different value.', ['%value' => $element['#value'], '%title' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementStateOptions() {
    $states = [];

    // Set default states that apply to the element/container and sub elements.
    $states += [
      'visible' => t('Visible'),
      'invisible' => t('Invisible'),
      'enabled' => t('Enabled'),
      'disabled' => t('Disabled'),
      'required' => t('Required'),
      'optional' => t('Optional'),
    ];

    // Set element type specific states.
    switch ($this->getPluginId()) {
      case 'checkbox':
        $states += [
          'checked' => t('Checked'),
          'unchecked' => t('Unchecked'),
        ];
        break;

      case 'details':
        $states += [
          'expanded' => t('Expanded'),
          'collapsed' => t('Collapsed'),
        ];
        break;
    }

    return $states;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#yamlform_key'];

    if ($inputs = $this->getElementSelectorInputsOptions($element)) {
      $selectors = [];
      foreach ($inputs as $input_name => $input_title) {
        $selectors[":input[name=\"{$name}[{$input_name}]\"]"] = $input_title;
      }
      return [$title => $selectors];
    }
    else {
      return [":input[name=\"$name\"]" => $title];
    }
  }

  /**
   * Get an element's (sub)inputs selectors as options.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An array of element (sub)input selectors.
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array $values) {}

  /**
   * {@inheritdoc}
   */
  public function postCreate(array &$element, YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postLoad(array &$element, YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preDelete(array &$element, YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, YamlFormSubmissionInterface $yamlform_submission, $update = TRUE) {}

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform_ui\Form\YamlFormUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $yamlform = $form_object->getYamlForm();

    /* Element settings */

    $form['element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element settings'),
      '#access' => TRUE,
      '#weight' => -50,
    ];
    $form['element']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('This is used as a descriptive label when displaying this form element.'),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['element']['description'] = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of the element used as help for the user when he/she uses the form.'),
    ];
    if ($this->isComposite()) {
      $form['element']['default_value'] = [
        '#type' => 'yamlform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the form element.'),
      ];
    }
    else {
      $form['element']['default_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the form element.'),
      ];
    }
    $form['element']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('The value of the form element.'),
    ];

    /* Form display */

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form display'),
      '#open' => FALSE,
    ];
    $form['form']['title_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Title display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'inline' => $this->t('Inline'),
        'invisible' => $this->t('Invisible'),
        'attribute' => $this->t('Attribute'),
      ],
      '#description' => $this->t('Determines the placement of the title.'),
    ];
    $form['form']['description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Description display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the placement of the description.'),
    ];
    $form['form']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the input. This can be used to prefix an input with a constant string. Examples: $, #, -.'),
      '#size' => 10,
    ];
    $form['form']['field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field suffix'),
      '#description' => $this->t('Text or code that is placed directly after the input. This can be used to add a unit to an input. Examples: lb, kg, %.'),
      '#size' => 10,
    ];
    $form['form']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Leaving blank will use the default size.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maxlength'),
      '#description' => $this->t('Leaving blank will use the default maxlength.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#description' => $this->t('Leaving blank will use the default rows.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('The placeholder will be shown in the element until the user starts entering a value.'),
    ];
    $form['form']['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('Contents should be visible (open) to the user.'),
      '#return_value' => TRUE,
    ];

    /* Flexbox item */

    $form['flex'] = [
      '#type' => 'details',
      '#title' => $this->t('Flexbox item'),
      '#description' => $this->t('Learn more about using <a href=":href">flexbox layouts</a>.', [':href' => 'http://www.w3schools.com/css/css3_flexbox.asp']),
      '#open' => FALSE,
    ];
    $flex_range = range(0, 12);
    $form['flex']['flex'] = [
      '#type' => 'select',
      '#title' => $this->t('Flex'),
      '#description' => $this->t('The flex property specifies the length of the item, relative to the rest of the flexible items inside the same container.') . '<br/>' .
      $this->t('Defaults to: %value', ['%value' => 1]),
      '#options' => [0 => $this->t('0 (none)')] + array_combine($flex_range, $flex_range),
    ];

    /* Custom attributes */

    $form['attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom attributes'),
      '#open' => FALSE,
    ];
    $form['attributes']['wrapper_attributes__class'] = self::getAttributesClassElement(
      $this->t('Wrapper CSS classes'),
      $this->t("Apply classes to the element's wrapper around both the field and its label. Select 'custom...' to enter custom classes."),
      $this->configFactory->get('yamlform.settings')->get('elements.wrapper_classes')
    );
    $form['attributes']['wrapper_attributes__style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper CSS style'),
      '#description' => $this->t("Apply custom styles to the element's wrapper around both the field and its label."),
    ];
    $form['attributes']['attributes__class'] = self::getAttributesClassElement(
      $this->t('Element CSS classes'),
      $this->t("Apply classes to the element. Select 'custom...' to enter custom classes."),
      $this->configFactory->get('yamlform.settings')->get('elements.classes')
    );
    $form['attributes']['attributes__style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element CSS style'),
      '#description' => $this->t('Apply custom styles to the element.'),
    ];

    /* Validation */

    // Placeholder form elements with #options.
    // @see \Drupal\yamlform\Plugin\YamlFormElement\OptionsBase::form
    $form['options'] = [];
    $form['options_other'] = [];

    $form['validation'] = [
      '#type' => 'details',
      '#title' => $this->t('Form validation'),
      '#open' => FALSE,
    ];
    $form['validation']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#description' => $this->t('Check this option if the user must enter a value.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['required_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom required error message'),
      '#description' => $this->t('If set, this message will be used when a required form element is empty, instead of the default "Field x is required." message.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[required]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['validation']['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#description' => $this->t('Check that all entered values for this element are unique. The same value is not allowed to be used twice.'),
      '#return_value' => TRUE,
    ];

    /* Conditional logic */

    $form['conditional'] = [
      '#type' => 'details',
      '#title' => $this->t('Conditional logic'),
      '#open' => FALSE,
    ];
    $form['conditional']['states'] = [
      '#type' => 'yamlform_element_states',
      '#state_options' => $this->getElementStateOptions(),
      '#selector_options' => $yamlform->getElementsSelectorOptions(),
    ];

    /* Submission display */

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission display'),
      '#open' => FALSE,
    ];
    $form['display']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $this->getFormats(),
    ];

    /* Element access */

    $operations = [
      'create' => [
        '#title' => $this->t('Create form submission'),
        '#description' => $this->t('Select roles and users that should be able to populate this element when creating a new submission.'),
      ],
      'update' => [
        '#title' => $this->t('Update form submission'),
        '#description' => $this->t('Select roles and users that should be able to update this element when updating an existing submission.'),
      ],
      'view' => [
        '#title' => $this->t('View form submission'),
        '#description' => $this->t('Select roles and users that should be able to view this element when viewing a submission.'),
      ],
    ];

    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Element access'),
      '#open' => FALSE,
    ];
    if (!$this->currentUser->hasPermission('administer yamlform') && !$this->currentUser->hasPermission('administer yamlform element access')) {
      $form['access'] = FALSE;
    }
    foreach ($operations as $operation => $operation_element) {
      $form['access']['access_' . $operation] = $operation_element + [
        '#type' => 'details',
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_roles'] = [
        '#type' => 'yamlform_roles',
        '#title' => $this->t('Roles'),
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_users'] = [
        '#type' => 'yamlform_users',
        '#title' => $this->t('Users'),
      ];
    }

    /* Administration */

    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Administration'),
      '#open' => FALSE,
    ];
    $form['admin']['private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private'),
      '#description' => $this->t('Private elements are shown only to users with results access.'),
      '#weight' => 50,
      '#return_value' => TRUE,
    ];
    $form['admin']['admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin title'),
      '#description' => $this->t('The admin title will be displayed when managing elements and viewing & downloading submissions.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_properties = $this->getDefaultProperties();
    $this->properties = YamlFormElementHelper::removePrefix($this->configuration) + $default_properties;
    $this->setConfigurationFormAttributes($this->properties);

    $form = $this->form($form, $form_state);

    // Set fieldset weights so that they appear first.
    foreach ($form as &$element) {
      if (is_array($element) && !isset($element['#weight']) && isset($element['#type']) && $element['#type'] == 'fieldset') {
        $element['#weight'] = -20;
      }
    }

    $this->setConfigurationFormDefaultValueRecursive($form, $this->properties);

    // Store 'type' as a hardcoded value and make sure it is always first.
    // Also always remove the 'yamlform_*' prefix from the type name.
    if (isset($this->properties['type'])) {
      $form['type'] = [
        '#type' => 'value',
        '#value' => preg_replace('/^yamlform_/', '', $this->properties['type']),
        '#parents' => ['properties', 'type'],
      ];
      unset($this->properties['type']);
    }

    // Allow custom properties (ie #attributes) to be added to the element.
    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom settings'),
      '#open' => $this->properties ? TRUE : FALSE,
      '#access' => $this->currentUser->hasPermission('edit yamlform source'),
    ];
    if ($api_url = $this->getPluginApiUrl()) {
      $t_args = [
        ':href' => $api_url->toString(),
        '%label' => $this->getPluginLabel(),
      ];
      $form['custom']['#description'] = $this->t('Read the %label element\'s <a href=":href">API documentation</a>.', $t_args);
    }

    $form['custom']['custom'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom properties'),
      '#description' => $this->t('Properties can include additional custom <a href=":attributes_href">#attributes</a>.', [':attributes_href' => 'https://api.drupal.org/api/drupal/developer!topics!forms_api_reference.html/7.x#attributes']) .
      ' ' .
      $this->t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added upon submission.') .
      '<br/>' .
      $this->t('These properties and callbacks are not allowed: @properties', ['@properties' => YamlFormArrayHelper::toString(YamlFormElementHelper::addPrefix(YamlFormElementHelper::$ignoredProperties))]),
      '#default_value' => Yaml::encode($this->properties),
      '#parents' => ['properties', 'custom'],
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['yamlform', 'yamlform-submission'],
        '#click_insert' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Set configuration form default values recursively.
   *
   * @param array $form
   *   A form render array.
   * @param array $properties
   *   The element's properties without hash prefix.
   *
   * @return bool
   *   TRUE is the form has any inputs.
   */
  protected function setConfigurationFormDefaultValueRecursive(array &$form, array &$properties) {
    $has_input = FALSE;

    foreach ($form as $property_name => &$property_element) {
      // Skip all properties.
      if (Element::property($property_name)) {
        continue;
      }

      // Skip Entity reference element 'selection_settings'.
      // @see \Drupal\yamlform\Plugin\YamlFormElement\YamlFormEntityReferenceTrait::form
      // @todo Fix entity reference AJAX and move code YamlFormEntityReferenceTrait.
      if (!empty($property_element['#tree']) && $property_name == 'selection_settings') {
        unset($properties[$property_name]);
        $property_element['#parents'] = ['properties', $property_name];
        $has_input = TRUE;
        continue;
      }

      // Determine if the property element is an input using the form element
      // manager.
      $is_input = $this->elementManager->getElementInstance($property_element)->isInput($property_element);
      if ($is_input) {
        if (isset($properties[$property_name])) {
          // If this property exists, then set its default value.
          $this->setConfigurationFormDefaultValue($form, $properties, $property_element, $property_name);
          $has_input = TRUE;
        }
        else {
          // Else completely remove the property element from the form.
          unset($form[$property_name]);
        }
      }
      else {
        // Recurse down this container and see if it's children have inputs.
        // Note: #access is used to protected containers that should always
        // be visible.
        $container_has_input = $this->setConfigurationFormDefaultValueRecursive($property_element, $properties);
        if ($container_has_input) {
          $has_input = TRUE;
        }
        elseif (empty($form[$property_name]['#access'])) {
          unset($form[$property_name]);
        }
      }
    }

    return $has_input;
  }

  /**
   * Set an element's configuration form element default value.
   *
   * @param array $form
   *   An element's configuration form.
   * @param array $properties
   *   The element's properties without hash prefix.
   * @param array $property_element
   *   The form input used to set an element's property.
   * @param string $property_name
   *   THe property's name.
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$properties, array &$property_element, $property_name) {
    $default_value = $properties[$property_name];
    $type = (isset($property_element['#type'])) ? $property_element['#type'] : NULL;

    switch ($type) {
      case 'entity_autocomplete':
        $target_type = $property_element['#target_type'];
        $target_storage = $this->entityTypeManager->getStorage($target_type);
        if (!empty($property_element['#tags'])) {
          $property_element['#default_value'] = ($default_value) ? $target_storage->loadMultiple($default_value) : [];
        }
        else {
          $property_element['#default_value'] = ($default_value) ? $target_storage->load($default_value) : NULL;
        }
        break;

      case 'yamlform_codemirror':
        if ($property_element['#mode'] == 'yaml' && is_array($default_value)) {
          $property_element['#default_value'] = Yaml::encode($default_value);
        }
        else {
          $property_element['#default_value'] = $default_value;
        }
        break;

      case 'radios':
      case 'select':
        // Handle invalid default_value throwing
        // "An illegal choice has been detected..." error.
        if (!is_array($default_value) && isset($property_element['#options'])) {
          $flattened_options = OptGroup::flattenOptions($property_element['#options']);
          if (!isset($flattened_options[$default_value])) {
            $default_value = NULL;
          }
        }
        $property_element['#default_value'] = $default_value;
        break;

      default:
        // Convert default_value array into a comma delimited list.
        // This is applicable to elements that support #multiple #options.
        if (is_array($default_value) && $property_name == 'default_value' && !$this->isComposite()) {
          $property_element['#default_value'] = implode(', ', $default_value);
        }
        else {
          $property_element['#default_value'] = $default_value;
        }
        break;

    }

    $property_element['#parents'] = ['properties', $property_name];
    unset($properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $properties = $this->getConfigurationFormProperties($form, $form_state);
    if ($ignored_properties = YamlFormElementHelper::getIgnoredProperties($properties)) {
      $t_args = [
        '@properties' => YamlFormArrayHelper::toString($ignored_properties),
      ];
      $form_state->setErrorByName('custom', t('Element contains ignored/unsupported properties: @properties.', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $properties = $form_state->getValues();

    if (isset($properties['custom'])) {
      // Decode and append custom properties.
      if ($properties['custom']) {
        $properties += Yaml::decode($properties['custom']);
      }
      unset($properties['custom']);
    }

    // Get default properties so that they can be unset below.
    $default_properties = $this->getDefaultProperties();

    // Remove all hash prefixes so that we can filter out any default
    // properties.
    YamlFormElementHelper::removePrefix($properties);

    $this->getConfigurationFormAttributes($properties);

    // Build a temp element used to see if multiple value and/or composite
    // elements need to be supported.
    $element = YamlFormElementHelper::addPrefix($properties);
    foreach ($properties as $property_name => $property_value) {
      if (!isset($default_properties[$property_name])) {
        continue;
      }

      $this->getConfigurationFormProperty($properties, $property_name, $property_value, $element);

      if ($default_properties[$property_name] == $properties[$property_name]) {
        unset($properties[$property_name]);
      }
    }

    // Make sure #type is always first.
    if (isset($properties['type'])) {
      $properties = ['type' => $properties['type']] + $properties;
    }

    return YamlFormElementHelper::addPrefix($properties);
  }

  /**
   * Set configuration form attributes from properties.
   *
   * @param array $properties
   *   An array of element properties.
   */
  protected function setConfigurationFormAttributes(&$properties) {
    foreach ($properties as $property => $value) {
      // Attributes are delimited using '__'.
      if (strpos($property, 'attributes__') === FALSE) {
        continue;
      }

      list($custom_parent, $custom_key) = explode('__', $property);
      if (isset($properties[$custom_parent][$custom_key])) {
        $properties[$property] = $properties[$custom_parent][$custom_key];
        unset($properties[$custom_parent][$custom_key]);
        if (empty($properties[$custom_parent])) {
          unset($properties[$custom_parent]);
        }
      }
    }
  }

  /**
   * Get configuration form attributes from properties.
   *
   * @param array $properties
   *   An array of element properties.
   */
  protected function getConfigurationFormAttributes(array &$properties) {
    foreach ($properties as $property => $value) {
      // Attributes are delimited using '__'.
      if (strpos($property, 'attributes__') === FALSE) {
        continue;
      }

      list($custom_parent, $custom_key) = explode('__', $property);
      if ($value) {
        // Convert key/value pairs to a simple indexed array of values.
        if (is_array($value)) {
          $value = array_values($value);
        }
        $properties[$custom_parent][$custom_key] = $value;
      }
      unset($properties[$property]);
    }
  }

  /**
   * Get configuration property value.
   *
   * @param array $properties
   *   An associative array of submitted properties.
   * @param string $property_name
   *   The property's name.
   * @param mixed $property_value
   *   The property's value.
   * @param array $element
   *   The element whose properties are being updated.
   */
  protected function getConfigurationFormProperty(array &$properties, $property_name, $property_value, array $element) {
    if ($property_name == 'default_value' && trim($property_value) && $this->hasMultipleValues($element)) {
      if ($this->isComposite()) {
        $properties[$property_name] = Yaml::decode($property_value);
      }
      else {
        $properties[$property_name] = preg_split('/\s*,\s*/', $property_value);
      }
    }
    else {
      // Decode raw YAML into an associative array.
      if ($this->isPropertyArray($property_name) && is_string($property_value)) {
        // Handle rare case where single array value is not parsed correctly.
        if (preg_match('/^- (.*?)\s*$/', $property_value, $match)) {
          $property_value = [$match[1]];
        }
        else {
          try {
            $property_value = Yaml::decode($property_value);
          }
          catch (\Exception $exception) {
            // Do nothing with YAML decoding exception.
          }
        }
      }

      $properties[$property_name] = $property_value;
    }
  }

  /**
   * Determine is an element's property should be an associative array.
   *
   * @param string $property_name
   *   An element's property name.
   *
   * @return bool
   *   TRUE is the element's default property value is an array.
   */
  protected function isPropertyArray($property_name) {
    $default_properties = $this->getDefaultProperties();
    return is_array($default_properties[$property_name]);
  }

  /**
   * Get element for entering an element's or wrappers attribute class(es).
   *
   * @param string $title
   *   The element's title.
   * @param string $description
   *   The element's description.
   * @param string $classes
   *   Available classes.
   *
   * @return array
   *   An element for entering an element's class(es).
   */
  public static function getAttributesClassElement($title, $description, $classes) {
    $classes = preg_split('/\r?\n/', trim($classes));
    return [
      '#title' => $title,
      '#description' => $description,
      '#type' => 'yamlform_select_other',
      '#multiple' => TRUE,
      '#options' => [YamlFormSelectOther::OTHER_OPTION => t('custom...')] + array_combine($classes, $classes),
      '#other__option_delimiter' => ' ',
      '#other__placeholder' => t('Enter custom classes...'),
      '#attached' => ['library' => ['yamlform/yamlform.element.select2']],
      '#attributes' => ['class' => ['js-yamlform-select2', 'yamlform-select2']],
    ];
  }

}
