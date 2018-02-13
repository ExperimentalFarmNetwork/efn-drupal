<?php

namespace Drupal\yamlform\Plugin\YamlFormHandler;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\yamlform\Element\YamlFormSelectOther;
use Drupal\yamlform\YamlFormHandlerBase;
use Drupal\yamlform\YamlFormHandlerMessageInterface;
use Drupal\yamlform\YamlFormSubmissionInterface;
use Drupal\yamlform\YamlFormTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a form submission.
 *
 * @YamlFormHandler(
 *   id = "email",
 *   label = @Translation("Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a form submission via an email."),
 *   cardinality = \Drupal\yamlform\YamlFormHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\yamlform\YamlFormHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class EmailYamlFormHandler extends YamlFormHandlerBase implements YamlFormHandlerMessageInterface {

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token manager.
   *
   * @var \Drupal\yamlform\YamlFormTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, YamlFormTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
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
      $container->get('logger.factory')->get('yamlform.email'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('yamlform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#settings' => $this->getEmailConfiguration(),
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'to_mail' => 'default',
      'cc_mail' => '',
      'bcc_mail' => '',
      'from_mail' => 'default',
      'from_name' => 'default',
      'subject' => 'default',
      'body' => 'default',
      'excluded_elements' => [],
      'html' => TRUE,
      'attachments' => FALSE,
      'debug' => FALSE,
    ];
  }

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    if (isset($this->defaultValues)) {
      return $this->defaultValues;
    }

    $yamlform_settings = $this->configFactory->get('yamlform.settings');
    $site_settings = $this->configFactory->get('system.site');
    $body_format = ($this->configuration['html']) ? 'html' : 'text';
    $default_mail = $yamlform_settings->get('mail.default_to_mail') ?: $site_settings->get('mail') ?: ini_get('sendmail_from');

    $this->defaultValues = [
      'to_mail' => $default_mail,
      'cc_mail' => $default_mail,
      'bcc_mail' => $default_mail,
      'from_mail' => $default_mail,
      'from_name' => $yamlform_settings->get('mail.default_from_name') ?: $site_settings->get('name'),
      'subject' => $yamlform_settings->get('mail.default_subject') ?: 'Form submission from: [yamlform_submission:source-entity]',
      'body' => $this->getBodyDefaultValues($body_format),
    ];

    return $this->defaultValues;
  }

  /**
   * Get configuration default value.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return string|array
   *   Configuration default value.
   */
  protected function getDefaultConfigurationValue($name) {
    $default_values = $this->getDefaultConfigurationValues();
    return $default_values[$name];
  }

  /**
   * Get mail configuration values.
   *
   * @return array
   *   An associative array containing email configuration values.
   */
  protected function getEmailConfiguration() {
    $configuration = $this->getConfiguration();
    $email = [];
    foreach ($configuration['settings'] as $key => $value) {
      if ($value === 'default') {
        $email[$key] = $this->getDefaultConfigurationValue($key);
      }
      else {
        $email[$key] = $value;
      }
    }
    return $email;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $mail_element_options = [];
    $text_element_options = [];
    $elements = $this->yamlform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key;
      if (isset($element['#type']) && in_array($element['#type'], ['email', 'hidden', 'value', 'select', 'radios', 'textfield', 'yamlform_email_multiple', 'yamlform_email_confirm'])) {
        // Note: Token must use the :raw form mail elements.
        // For example a select menu's option value would be used to route an
        // email address.
        $mail_element_options["[yamlform_submission:values:$key:raw]"] = $title;
      }
      $text_element_options["[yamlform_submission:values:$key:value]"] = $title;
    }

    $default_optgroup = (string) $this->t('Default');
    $elements_optgroup = (string) $this->t('Elements');

    // Disable client-side HTML5 validation which is having issues with hidden
    // element validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    $form['#attributes']['novalidate'] = 'novalidate';

    // To.
    $form['to'] = [
      '#type' => 'details',
      '#title' => $this->t('Send to'),
      '#open' => TRUE,
    ];
    $form['to']['to_mail'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('To email'),
      '#options' => [
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom to email address...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('to_mail')],
        $elements_optgroup => $mail_element_options,
      ],
      '#other__placeholder' => $this->t('Enter to email address...'),
      '#other__type' => 'yamlform_email_multiple',
      '#other__allow_tokens' => TRUE,
      '#required' => TRUE,
      '#parents' => ['settings', 'to_mail'],
      '#default_value' => $this->configuration['to_mail'],
    ];
    $form['to']['cc_mail'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('CC email'),
      '#options' => [
        '' => '',
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom CC email address...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('cc_mail')],
        $elements_optgroup => $mail_element_options,
      ],
      '#other__placeholder' => $this->t('Enter CC email address...'),
      '#other__type' => 'yamlform_email_multiple',
      '#parents' => ['settings', 'cc_mail'],
      '#other__allow_tokens' => TRUE,
      '#default_value' => $this->configuration['cc_mail'],
    ];
    $form['to']['bcc_mail'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('BCC email'),
      '#options' => [
        '' => '',
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom BCC email address...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('bcc_mail')],
        $elements_optgroup => $mail_element_options,
      ],
      '#other__placeholder' => $this->t('Enter BCC email address...'),
      '#other__type' => 'yamlform_email_multiple',
      '#other__allow_tokens' => TRUE,
      '#parents' => ['settings', 'bcc_mail'],
      '#default_value' => $this->configuration['bcc_mail'],
    ];

    // From.
    $form['from'] = [
      '#type' => 'details',
      '#title' => $this->t('Send from'),
      '#open' => TRUE,
    ];
    $form['from']['from_mail'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('From email'),
      '#options' => [
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom from email address...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('from_mail')],
        $elements_optgroup => $mail_element_options,
      ],
      '#other__placeholder' => $this->t('Enter from email address...'),
      '#other__type' => 'yamlform_email_multiple',
      '#other__allow_tokens' => TRUE,
      '#required' => TRUE,
      '#parents' => ['settings', 'from_mail'],
      '#default_value' => $this->configuration['from_mail'],
    ];
    $form['from']['from_name'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('From name'),
      '#options' => [
        '' => '',
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom from name...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('from_name')],
        $elements_optgroup => $text_element_options,
      ],
      '#other__placeholder' => $this->t('Enter from name...'),
      '#parents' => ['settings', 'from_name'],
      '#default_value' => $this->configuration['from_name'],
    ];

    // Message.
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
    ];
    $form['message']['subject'] = [
      '#type' => 'yamlform_select_other',
      '#title' => $this->t('Subject'),
      '#options' => [
        YamlFormSelectOther::OTHER_OPTION => $this->t('Custom subject...'),
        $default_optgroup => ['default' => $this->getDefaultConfigurationValue('subject')],
        $elements_optgroup => $text_element_options,
      ],
      '#other__placeholder' => $this->t('Enter subject...'),
      '#required' => TRUE,
      '#parents' => ['settings', 'subject'],
      '#default_value' => $this->configuration['subject'],
    ];

    // Body.
    // Building a custom select other element that toggles between
    // HTML (CKEditor) and Plain text (CodeMirror) custom body elements.
    $body_options = [
      YamlFormSelectOther::OTHER_OPTION => $this->t('Custom body...'),
      'default' => $this->t('Default'),
      $elements_optgroup => $text_element_options,
    ];

    $body_default_format = ($this->configuration['html']) ? 'html' : 'text';
    $body_default_values = $this->getBodyDefaultValues();
    if (isset($body_options[$this->configuration['body']])) {
      $body_default_value = $this->configuration['body'];
      $body_custom_default_value = $body_default_values[$body_default_format];
    }
    else {
      $body_default_value = YamlFormSelectOther::OTHER_OPTION;
      $body_custom_default_value = $this->configuration['body'];
    }
    $form['message']['body'] = [
      '#type' => 'select',
      '#title' => $this->t('Body'),
      '#options' => $body_options,
      '#required' => TRUE,
      '#parents' => ['settings', 'body'],
      '#default_value' => $body_default_value,
    ];
    foreach ($body_default_values as $format => $default_value) {
      // Custom body.
      $custom_default_value = ($format === $body_default_format) ? $body_custom_default_value : $default_value;
      if ($format == 'html') {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'yamlform_html_editor',
        ];
      }
      else {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'yamlform_codemirror',
          '#mode' => $format,
        ];
      }
      $form['message']['body_custom_' . $format] += [
        '#title' => $this->t('Body custom value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#parents' => ['settings', 'body_custom_' . $format],
        '#default_value' => $custom_default_value,
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => YamlFormSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
          'required' => [
            ':input[name="settings[body]"]' => ['value' => YamlFormSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];

      // Default body.
      $form['message']['body_default_' . $format] = [
        '#type' => 'yamlform_codemirror',
        '#mode' => $format,
        '#title' => $this->t('Body default value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#default_value' => $default_value,
        '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => 'default'],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];
    }
    $form['message']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included email values'),
      '#open' => $this->configuration['excluded_elements'] ? TRUE : FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'yamlform_excluded_elements',
      '#description' => $this->t('The selected elements will be included in the [yamlform_submission:values] token. Individual values may still be printed if explicitly specified as a [yamlform_submission:values:?] in the email body template.'),
      '#yamlform' => $this->yamlform,
      '#default_value' => $this->configuration['excluded_elements'],
      '#parents' => ['settings', 'excluded_elements'],
    ];

    // Settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
    ];
    $form['settings']['html'] = [
      '#type' => 'checkbox',
      '#title' => t('Send email as HTML'),
      '#return_value' => TRUE,
      '#access' => $this->supportsHtml(),
      '#parents' => ['settings', 'html'],
      '#default_value' => $this->configuration['html'],
    ];
    $form['settings']['attachments'] = [
      '#type' => 'checkbox',
      '#title' => t('Include files as attachments'),
      '#return_value' => TRUE,
      '#access' => $this->supportsAttachments(),
      '#parents' => ['settings', 'attachments'],
      '#default_value' => $this->configuration['attachments'],
    ];
    $form['settings']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, sent emails will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();

    // Set custom body based on the selected format.
    if ($values['body'] === YamlFormSelectOther::OTHER_OPTION) {
      $body_format = ($values['html']) ? 'html' : 'text';
      $values['body'] = $values['body_custom_' . $body_format];
    }
    unset(
      $values['body_custom_text'],
      $values['body_default_html']
    );

    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(YamlFormSubmissionInterface $yamlform_submission, $update = TRUE) {
    $is_results_disabled = $yamlform_submission->getYamlForm()->getSetting('results_disabled');
    $is_completed = ($yamlform_submission->getState() == YamlFormSubmissionInterface::STATE_COMPLETED);
    if ($is_results_disabled || $is_completed) {
      $message = $this->getMessage($yamlform_submission);
      $this->sendMessage($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(YamlFormSubmissionInterface $yamlform_submission) {
    $token_data = [
      'yamlform-submission-options' => [
        'email' => TRUE,
        'excluded_elements' => $this->configuration['excluded_elements'],
        'html' => ($this->configuration['html'] && $this->supportsHtml()),
      ],
    ];

    $message = $this->configuration;

    // Replace 'default' values and [tokens] with configuration default values.
    foreach ($message as $key => $value) {
      if ($value === 'default') {
        $message[$key] = $this->getDefaultConfigurationValue($key);
      }
      if (is_string($message[$key])) {
        $message[$key] = $this->tokenManager->replace($message[$key], $yamlform_submission, $token_data);
      }
    }

    // Trim the message body.
    $message['body'] = trim($message['body']);

    // Alter body based on the mail system sender.
    if ($this->configuration['html'] && $this->supportsHtml()) {
      switch ($this->getMailSystemSender()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }
    else {
      // Since Drupal might be rendering a token into the body as markup
      // we need to decode all HTML entities which are being sent as plain text.
      $message['body'] = html_entity_decode($message['body']);
    }

    // Add attachments.
    $message['attachments'] = [];
    if ($this->configuration['attachments'] && $this->supportsAttachments()) {
      $elements = $this->yamlform->getElementsInitializedAndFlattened();
      foreach ($elements as $key => $element) {
        if (!isset($element['#type']) || $element['#type'] != 'managed_file') {
          continue;
        }

        $fids = $yamlform_submission->getData($key);
        if (empty($fids)) {
          continue;
        }

        /** @var \Drupal\file\FileInterface[] $files */
        $files = File::loadMultiple(is_array($fids) ? $fids : [$fids]);
        foreach ($files as $file) {
          $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());
          $message['attachments'][] = [
            'filecontent' => file_get_contents($filepath),
            'filename' => $file->getFilename(),
            'filemime' => $file->getMimeType(),
            // Add URL to be used by resend webform.
            'file' => $file,
          ];
        }
      }
    }

    // Add form submission.
    $message['yamlform_submission'] = $yamlform_submission;

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $message) {
    // Send mail.
    $to = $message['to_mail'];
    $from = $message['from_mail'] . (($message['from_name']) ? ' <' . $message['from_name'] . '>' : '');
    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $this->mailManager->mail('yamlform', 'email.' . $this->getHandlerId(), $to, $current_langcode, $message, $from);

    // Log message.
    $context = [
      '@form' => $this->getYamlForm()->label(),
      '@title' => $this->label(),
    ];
    $this->logger->notice('@form form sent @title email.', $context);

    // Debug by displaying send email onscreen.
    if ($this->configuration['debug']) {
      $t_args = [
        '%from_name' => $message['from_name'],
        '%from_mail' => $message['from_mail'],
        '%to_mail' => $message['to_mail'],
        '%subject' => $message['subject'],
      ];
      $build = [];
      $build['message'] = [
        '#markup' => $this->t('%subject sent to %to_mail from %from_name [%from_mail].', $t_args),
        '#prefix' => '<b>',
        '#suffix' => '</b>',
      ];
      if ($message['html']) {
        $build['body'] = [
          '#markup' => $message['body'],
          '#allowed_tags' => Xss::getAdminTagList(),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      else {
        $build['body'] = [
          '#markup' => $message['body'],
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ];
      }
      drupal_set_message(\Drupal::service('renderer')->render($build), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resendMessageForm(array $message) {
    $element = [];
    $element['to_mail'] = [
      '#type' => 'yamlform_email_multiple',
      '#title' => $this->t('To email'),
      '#default_value' => $message['to_mail'],
    ];
    $element['from_mail'] = [
      '#type' => 'yamlform_email_multiple',
      '#title' => $this->t('From email'),
      '#required' => TRUE,
      '#default_value' => $message['from_mail'],
    ];
    $element['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#required' => TRUE,
      '#default_value' => $message['from_name'],
    ];
    $element['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $message['subject'],
    ];
    $body_format = ($this->configuration['html']) ? 'html' : 'text';
    $element['body'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => $body_format,
      '#title' => $this->t('Message (@format)', ['@format' => ($this->configuration['html']) ? $this->t('HTML') : $this->t('Plain text')]),
      '#rows' => 10,
      '#required' => TRUE,
      '#default_value' => $message['body'],
    ];
    $element['html'] = [
      '#type' => 'value',
      '#value' => $message['html'],
    ];
    $element['attachments'] = [
      '#type' => 'value',
      '#value' => $message['attachments'],
    ];

    // Display attached files.
    if ($message['attachments']) {
      $file_links = [];
      foreach ($message['attachments'] as $attachment) {
        $file_links[] = [
          '#theme' => 'file_link',
          '#file' => $attachment['file'],
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      $element['files'] = [
        '#type' => 'item',
        '#title' => $this->t('Attachments'),
        '#markup' => \Drupal::service('renderer')->render($file_links),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageSummary(array $message) {
    return [
      '#settings' => $message,
    ] + parent::getSummary();
  }

  /**
   * Check that HTML emails are supported.
   *
   * @return bool
   *   TRUE if HTML email is supported.
   */
  protected function supportsHtml() {
    return TRUE;
  }

  /**
   * Check that emailing files as attachments is supported.
   *
   * @return bool
   *   TRUE if emailing files as attachments is supported.
   */
  protected function supportsAttachments() {
    // If 'system.mail.interface.default' is 'test_mail_collector' allow
    // email attachments during testing.
    if (\Drupal::configFactory()->get('system.mail')->get('interface.default') == 'test_mail_collector') {
      return TRUE;
    }

    return \Drupal::moduleHandler()->moduleExists('mailsystem');
  }

  /**
   * Get the Mail System's sender module name.
   *
   * @return string
   *   The Mail System's sender module name.
   */
  protected function getMailSystemSender() {
    $mailsystem_config = $this->configFactory->get('mailsystem.settings');
    $mailsystem_sender = $mailsystem_config->get('yamlform.sender') ?: $mailsystem_config->get('defaults.sender');
    return $mailsystem_sender;
  }

  /**
   * Get message body default values, which can be formatted as text or html.
   *
   * @param string $format
   *   If a format (text or html) is provided the default value for the
   *   specified format is return. If no format is specified an associative
   *   array containing the text and html default body values will be returned.
   *
   * @return string|array
   *   A single (text or html) default body value or an associative array
   *   containing both the text and html default body values.
   */
  protected function getBodyDefaultValues($format = NULL) {
    $yamlform_settings = $this->configFactory->get('yamlform.settings');
    $formats = [
      'text' => $yamlform_settings->get('mail.default_body_text') ?: '[yamlform_submission:values]',
      'html' => $yamlform_settings->get('mail.default_body_html') ?: '[yamlform_submission:values]',
    ];
    return ($format === NULL) ? $formats : $formats[$format];
  }

}
