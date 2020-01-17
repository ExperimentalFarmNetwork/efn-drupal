<?php

namespace Drupal\sendgrid_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SendGridSettingsForm.
 *
 * @package Drupal\sendgrid_integration\Form
 */
class SendGridTestForm extends FormBase {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SendGridTestForm constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(MailManagerInterface $mailManager, LanguageManagerInterface $languageManager) {
    $this->mailManager = $mailManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendgrid_integration_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sendgrid_integration.settings');

    $form['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#default_value' => $config->get('test_defaults.from_name'),
      '#maxlength' => 128,
    ];
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $config->get('test_defaults.to'),
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['to_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To Name'),
      '#default_value' => $config->get('test_defaults.to_name'),
      '#maxlength' => 128,
    ];
    $form['reply_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply-To'),
      '#maxlength' => 128,
      '#default_value' => $config->get('test_defaults.reply_to'),
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('test_defaults.subject'),
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['include_attachment'] = [
      '#title' => $this->t('Include attachment'),
      '#type' => 'checkbox',
      '#description' => t('If checked, the Drupal icon will be included as an attachment with the test email.'),
      '#default_value' => TRUE,
    ];
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#rows' => 20,
      '#default_value' => $config->get('test_defaults.body.value'),
      '#format' => $config->get('test_defaults.body.format'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send test message'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()
      ->getEditable('sendgrid_integration.settings');
    $site_settings = $this->config('system.site');

    $config->set('test_defaults.to', $form_state->getValue('to'));
    $config->set('test_defaults.subject', $form_state->getValue('subject'));
    $config->set('test_defaults.body.value', $form_state->getValue([
      'body',
      'value',
    ]));
    $config->set('test_defaults.body.format', $form_state->getValue([
      'body',
      'format',
    ]));
    $config->set('test_defaults.from_name', $form_state->getValue('from_name'));
    $config->set('test_defaults.to_name', $form_state->getValue('to_name'));
    $config->set('test_defaults.reply_to', $form_state->getValue('reply_to'));
    $config->save();

    $params = $config->get('test_defaults');

    $params['include_test_attachment'] = $form_state->getValue('include_attachment');
    $params['body'] = check_markup($params['body']['value'], $params['body']['format']);

    // Attempt to send the email and post a message if it was successful.
    if ($config->get('test_defaults.from_name')) {
      $from = $config->get('test_defaults.from_name') . ' <' . $site_settings->get('mail') . '>';
    }
    else {
      $from = $site_settings->get('mail');
    }
    $result = $this->mailManager->mail('sendgrid_integration', 'test', $config->get('test_defaults.to'), $this->languageManager->getDefaultLanguage()
      ->getId(), $params, $from);
    if (isset($result['result']) && $result['result'] == TRUE) {
      drupal_set_message($this->t('SendGrid test email sent from %from to %to.', [
        '%from' => $from,
        '%to' => $config->get('test_defaults.to'),
      ]));
    }
  }

}
