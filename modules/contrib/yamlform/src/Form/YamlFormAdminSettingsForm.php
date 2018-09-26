<?php

namespace Drupal\yamlform\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Utility\YamlFormArrayHelper;
use Drupal\yamlform\YamlFormElementManagerInterface;
use Drupal\yamlform\YamlFormSubmissionExporterInterface;
use Drupal\yamlform\YamlFormTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure form admin settings for this site.
 */
class YamlFormAdminSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form element manager.
   *
   * @var \Drupal\yamlform\YamlFormElementManagerInterface
   */
  protected $elementManager;

  /**
   * The form submission exporter.
   *
   * @var \Drupal\yamlform\YamlFormSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * The token manager.
   *
   * @var \Drupal\yamlform\YamlFormTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * An array of element types.
   *
   * @var array
   */
  protected $elementTypes;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yamlform_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yamlform.settings'];
  }

  /**
   * Constructs a YamlFormAdminSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $third_party_settings_manager
   *   The module handler.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   * @param \Drupal\yamlform\YamlFormSubmissionExporterInterface $submission_exporter
   *   The form submission exporter.
   * @param \Drupal\yamlform\YamlFormTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $third_party_settings_manager, YamlFormElementManagerInterface $element_manager, YamlFormSubmissionExporterInterface $submission_exporter, YamlFormTokenManagerInterface $token_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $third_party_settings_manager;
    $this->elementManager = $element_manager;
    $this->submissionExporter = $submission_exporter;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.yamlform.element'),
      $container->get('yamlform_submission.exporter'),
      $container->get('yamlform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yamlform.settings');
    $settings = $config->get('settings');
    $element_plugins = $this->elementManager->getInstances();

    // Page.
    $form['page'] = [
      '#type' => 'details',
      '#title' => $this->t('Page default settings'),
      '#tree' => TRUE,
    ];
    $form['page']['default_page_base_path']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base path for form URLs'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_page_base_path'),
    ];

    // Form.
    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form default settings'),
      '#tree' => TRUE,
    ];
    $form['form']['default_form_closed_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default closed message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_closed_message'),
    ];
    $form['form']['default_form_exception_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default closed exception message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_exception_message'),
    ];
    $form['form']['default_form_submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default submit button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_form_submit_label'],
    ];
    $form['form']['default_form_confidential_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default confidential message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_confidential_message'),
    ];
    $form['form']['default_form_disable_back']  = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable back button for all forms'),
      '#description' => $this->t('If checked, users will not be allowed to navigate back to the form using the browsers back button.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('settings.default_form_disable_back'),
    ];
    $form['form']['default_form_unsaved']  = [
      '#type' => 'checkbox',
      '#title' => $this->t('Warn users about unsaved changes'),
      '#description' => $this->t('If checked, users will be displayed a warning message when they navigate away from a form with unsaved changes.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('settings.default_form_unsaved'),
    ];
    $form['form']['default_form_novalidate']  = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable client-side validation for all forms'),
      '#description' => $this->t('If checked, the <a href=":href">novalidate</a> attribute, which disables client-side validation, will be added to all forms.', [':href' => 'http://www.w3schools.com/tags/att_form_novalidate.asp']),
      '#return_value' => TRUE,
      '#default_value' => $config->get('settings.default_form_novalidate'),
    ];
    $form['form']['default_form_details_toggle']  = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display collapse/expand all details link'),
      '#description' => $this->t('If checked, an expand/collapse all (details) link will be added to all forms with two or more details elements.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('settings.default_form_details_toggle'),
    ];
    $form['form']['form_classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Form CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Form CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.form_classes'),
    ];
    $form['form']['button_classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Button CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in "Button CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.button_classes'),
    ];

    // Wizard.
    $form['wizard'] = [
      '#type' => 'details',
      '#title' => $this->t('Wizard default settings'),
      '#tree' => TRUE,
    ];
    $form['wizard']['default_wizard_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_prev_button_label'],
    ];
    $form['wizard']['default_wizard_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard next page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_next_button_label'],
    ];
    $form['wizard']['default_wizard_start_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard start label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_start_label'],
    ];
    $form['wizard']['default_wizard_complete_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard end label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_complete_label'],
    ];

    // Preview.
    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview default settings'),
      '#tree' => TRUE,
    ];
    $form['preview']['default_preview_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_next_button_label'],
    ];
    $form['preview']['default_preview_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_prev_button_label'],
    ];
    $form['preview']['default_preview_message'] = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default preview message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_preview_message'],
    ];

    // Draft.
    $form['draft'] = [
      '#type' => 'details',
      '#title' => $this->t('Draft default settings'),
      '#tree' => TRUE,
    ];
    $form['draft']['default_draft_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default draft button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_draft_button_label'],
    ];
    $form['draft']['default_draft_saved_message'] = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default draft save message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_draft_saved_message'],
    ];
    $form['draft']['default_draft_loaded_message'] = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default draft load message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_draft_loaded_message'],
    ];

    $form['confirmation'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation default settings'),
      '#tree' => TRUE,
    ];
    $form['confirmation']['default_confirmation_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default confirmation message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_confirmation_message'),
    ];
    $form['confirmation']['default_confirmation_back_label']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Default confirmation back label'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_confirmation_back_label'),
    ];
    $form['confirmation']['confirmation_classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Confirmation CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.confirmation_classes'),
    ];
    $form['confirmation']['confirmation_back_classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Confirmation back link CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation back link CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.confirmation_back_classes'),
    ];

    // Limit.
    $form['limit'] = [
      '#type' => 'details',
      '#title' => $this->t('Limit default settings'),
      '#tree' => TRUE,
    ];
    $form['limit']['default_limit_total_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default total submissions limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_total_message'),
    ];
    $form['limit']['default_limit_user_message']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Default per user submission limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_user_message'),
    ];

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Element default settings'),
      '#tree' => TRUE,
    ];
    $form['elements']['allowed_tags'] = [
      '#type' => 'yamlform_radios_other',
      '#title' => $this->t('Allowed tags'),
      '#options' => [
        'admin' => $this->t('Admin tags Excludes: script, iframe, etc...'),
        'html' => $this->t('HTML tags: Includes only @html_tags.', ['@html_tags' => YamlFormArrayHelper::toString(Xss::getHtmlTagList())]),
      ],
      '#other__option_label' => $this->t('Custom tags'),
      '#other__placeholder' => $this->t('Enter multiple tags delimited using spaces'),
      '#other__default_value' => implode(' ', Xss::getAdminTagList()),
      '#other__maxlength' => 1000,
      '#required' => TRUE,
      '#description' => $this->t('Allowed tags are applied to any element property that may contain HTML markup. This properties include #title, #description, #field_prefix, and #field_suffix'),
      '#default_value' => $config->get('elements.allowed_tags'),
    ];
    $form['elements']['wrapper_classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Wrapper CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Wrapper CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('elements.wrapper_classes'),
    ];
    $form['elements']['classes'] = [
      '#type' => 'yamlform_codemirror',
      '#title' => $this->t('Element CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Element CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('elements.classes'),
    ];
    $form['elements']['default_description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Default description display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the default placement of the description for all form elements.'),
      '#default_value' => $config->get('elements.default_description_display'),
    ];
    $form['elements']['default_google_maps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
      '#default_value' => $config->get('elements.default_google_maps_api_key'),
    ];

    // (Excluded) Types.
    $types_header = [
      'title' => ['data' => $this->t('Title')],
      'type' => ['data' => $this->t('Type')],
    ];
    $this->elementTypes = [];
    $types_options = [];
    foreach ($element_plugins as $element_id => $element_plugin) {
      $this->elementTypes[$element_id] = $element_id;
      $types_options[$element_id] = [
        'title' => $element_plugin->getPluginLabel(),
        'type' => $element_plugin->getTypeName(),
      ];
    }
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Element types'),
      '#description' => $this->t('Select available element types'),
    ];
    $form['types']['excluded_types'] = [
      '#type' => 'tableselect',
      '#header' => $types_header,
      '#options' => $types_options,
      '#required' => TRUE,
      '#default_value' => array_diff($this->elementTypes, $config->get('elements.excluded_types')),
    ];

    // File.
    $form['file'] = [
      '#type' => 'details',
      '#title' => $this->t('File upload default settings'),
      '#tree' => TRUE,
    ];
    $form['file']['file_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow files to be uploaded to public file system.'),
      '#description' => $this->t('Public files upload destination is dangerous for forms that are available to anonymous and/or untrusted users.') . ' ' .
        $this->t('For more information see:') . ' <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>',
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.file_public'),
    ];
    $form['file']['default_max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default maximum upload size'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => function_exists('file_upload_max_size') ? format_size(file_upload_max_size()) : $this->t('N/A')]),
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
      '#size' => 10,
      '#default_value' => $config->get('file.default_max_filesize'),
    ];
    $file_types = [
      'managed_file' => 'managed file',
      'audio_file' => 'audio file',
      'document_file' => 'document file',
      'image_file' => 'image file',
      'video_file' => 'video file',
    ];
    foreach ($file_types as $file_type_name => $file_type_title) {
      $form['file']["default_{$file_type_name}_extensions"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default allowed @title extensions', ['@title' => $file_type_title]),
        '#description' => $this->t('Separate extensions with a space and do not include the leading dot.'),
        '#element_validate' => [[get_class($this), 'validateExtensions']],
        '#required' => TRUE,
        '#maxlength' => 256,
        '#default_value' => $config->get("file.default_{$file_type_name}_extensions"),
      ];
    }

    // Format.
    $form['format'] = [
      '#type' => 'details',
      '#title' => $this->t('Format default settings'),
      '#tree' => TRUE,
    ];
    foreach ($element_plugins as $element_id => $element_plugin) {
      $formats = $element_plugin->getFormats();
      // Make sure the element has formats.
      if (empty($formats)) {
        continue;
      }

      // Skip if the element just uses the default 'value' format.
      if (count($formats) == 1 && isset($formats['value'])) {
        continue;
      }

      // Append formats name to formats label.
      foreach ($formats as $format_name => $format_label) {
        $formats[$format_name] = new FormattableMarkup('@label (@name)', ['@label' => $format_label, '@name' => $format_name]);
      }

      // Create empty format since the select element is not required.
      // @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Select.php/class/Select/8
      $formats = ['' => '<' . $this->t('Default') . '>'] + $formats;

      $default_format = $element_plugin->getDefaultFormat();
      $default_format_label = (isset($formats[$default_format])) ? $formats[$default_format] : $default_format;
      $element_plugin_definition = $element_plugin->getPluginDefinition();
      $element_plugin_label = $element_plugin_definition['label'];

      $form['format'][$element_id] = [
        '#type' => 'select',
        '#title' => new FormattableMarkup('@label (@id)', ['@label' => $element_plugin_label, '@id' => $element_plugin->getTypeName()]),
        '#description' => $this->t('Defaults to: %value', ['%value' => $default_format_label]),
        '#options' => $formats,
        '#default_value' => $config->get("format.$element_id"),
      ];
    }

    // Mail.
    $form['mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Email default settings'),
      '#tree' => TRUE,
    ];
    $form['mail']['default_from_mail']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Default email from address'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_from_mail'),
    ];
    $form['mail']['default_from_name']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Default email from name'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_from_name'),
    ];
    $form['mail']['default_subject']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Default email subject'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_subject'),
    ];
    $form['mail']['default_body_text']  = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Default email body (Plain text)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_text'),
    ];
    $form['mail']['default_body_html']  = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'html',
      '#title' => $this->t('Default email body (HTML)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_html'),
    ];
    $form['mail']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Export.
    $form['export'] = [
      '#type' => 'details',
      '#title' => $this->t('Export default settings'),
    ];
    $export_options = NestedArray::mergeDeep($config->get('export') ?: [],
      $this->submissionExporter->getValuesFromInput($form_state->getUserInput())
    );
    $export_form_state = new FormState();
    $this->submissionExporter->buildExportOptionsForm($form, $export_form_state, $export_options);

    // Batch.
    $form['batch'] = [
      '#type' => 'details',
      '#title' => $this->t('Batch settings'),
      '#tree' => TRUE,
    ];
    $form['batch']['default_batch_export_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch export size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_export_size'),
    ];
    $form['batch']['default_batch_update_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch update size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_update_size'),
    ];
    $form['batch']['default_batch_delete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch delete size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_delete_size'),
    ];

    // Test.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test settings'),
      '#tree' => TRUE,
    ];
    $form['test']['types'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element type'),
      '#description' => $this->t("Above test data is keyed by FAPI element #type."),
      '#default_value' => $config->get('test.types'),
    ];
    $form['test']['names'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element name'),
      '#description' => $this->t("Above test data is keyed by full or partial element names. For example, Using 'zip' will populate fields that are named 'zip' and 'zip_code' but not 'zipcode' or 'zipline'."),
      '#default_value' => $config->get('test.names'),
    ];

    // UI.
    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface settings'),
      '#tree' => TRUE,
    ];
    $form['ui']['video_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Video display'),
      '#description' => $this->t('Controls how videos are displayed in inline help and within the global help section.'),
      '#options' => [
        'dialog' => $this->t('Dialog'),
        'link' => $this->t('External link'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $config->get('ui.video_display'),
    ];
    $form['ui']['details_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save details open/close state'),
      '#description' => $this->t('If checked, all <a href=":details_href">Details</a> element\'s open/close state will be saved using <a href=":local_storage_href">Local Storage</a>.', [
        ':details_href' => 'http://www.w3schools.com/tags/tag_details.asp',
        ':local_storage_href' => 'http://www.w3schools.com/html/html5_webstorage.asp',
      ]),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.details_save'),
    ];
    $form['ui']['dialog_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable dialogs'),
      '#description' => $this->t('If checked, all modal dialogs (ie popups) will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.dialog_disabled'),
    ];
    $form['ui']['html_editor_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable HTML editor'),
      '#description' => $this->t('If checked, all HTML editor will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.html_editor_disabled'),
    ];

    // Library.
    $form['library'] = [
      '#type' => 'details',
      '#title' => $this->t('Library settings'),
      '#tree' => TRUE,
    ];
    $form['library']['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use CDN'),
      '#description' => $this->t('If checked, all warnings about missing libraries will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('library.cdn'),
    ];
    $form['library']['cdn_message'] = [
      '#type' => 'yamlform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Note that it is in general not a good idea to load libraries from a CDN; avoid this if possible. It introduces more points of failure both performance- and security-wise, requires more TCP/IP connections to be set up and these external assets are usually not in the browser cache anyway.'),
      '#states' => [
        'visible' => [
          ':input[name="library[cdn]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('page')
      + $form_state->getValue('form')
      + $form_state->getValue('wizard')
      + $form_state->getValue('preview')
      + $form_state->getValue('draft')
      + $form_state->getValue('confirmation')
      + $form_state->getValue('limit');

    // Trigger update all form paths if the 'default_page_base_path' changed.
    $update_paths = ($settings['default_page_base_path'] != $this->config('yamlform.settings')->get('settings.default_page_base_path')) ? TRUE : FALSE;

    $config = $this->config('yamlform.settings');

    // Convert list of included types to excluded types.
    $excluded_types = array_diff($this->elementTypes, array_filter($form_state->getValue('excluded_types')));
    ksort($excluded_types);

    $config->set('settings', $settings);
    $config->set('elements', $form_state->getValue('elements') + ['excluded_types' => $excluded_types]);
    $config->set('file', $form_state->getValue('file'));
    $config->set('format', array_filter($form_state->getValue('format')));
    $config->set('mail', $form_state->getValue('mail'));
    $config->set('export', $this->submissionExporter->getValuesFromInput($form_state->getValues()));
    $config->set('batch', $form_state->getValue('batch'));
    $config->set('test', $form_state->getValue('test'));
    $config->set('ui', $form_state->getValue('ui'));
    $config->set('library', $form_state->getValue('library'));
    $config->save();
    if ($update_paths) {
      /** @var \Drupal\yamlform\YamlFormInterface[] $yamlforms */
      $yamlforms = YamlForm::loadMultiple();
      foreach ($yamlforms as $yamlform) {
        $yamlform->updatePaths();
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Wrapper for FileItem::validateExtensions.
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (class_exists('\Drupal\file\Plugin\Field\FieldType\FileItem')) {
      FileItem::validateExtensions($element, $form_state);
    }
  }

  /**
   * Wrapper for FileItem::validateMaxFilesize.
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    if (class_exists('\Drupal\file\Plugin\Field\FieldType\FileItem')) {
      FileItem::validateMaxFilesize($element, $form_state);
    }
  }

}
