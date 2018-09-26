<?php

namespace Drupal\yamlform\Plugin\YamlFormHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormHandlerBase;
use Drupal\yamlform\YamlFormSubmissionInterface;
use Drupal\yamlform\YamlFormTokenManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission remote post handler.
 *
 * @YamlFormHandler(
 *   id = "remote_post",
 *   label = @Translation("Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts form submissions to a URL."),
 *   cardinality = \Drupal\yamlform\YamlFormHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\yamlform\YamlFormHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class RemotePostYamlFormHandler extends YamlFormHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The token manager.
   *
   * @var \Drupal\yamlform\YamlFormTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ModuleHandlerInterface $module_handler, ClientInterface $http_client, YamlFormTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->moduleHandler = $module_handler;
    $this->httpClient = $http_client;
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
      $container->get('logger.factory')->get('yamlform.remote_post'),
      $container->get('module_handler'),
      $container->get('http_client'),
      $container->get('yamlform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();

    // If the saving of results is disabled clear update and delete URL.
    if ($this->getYamlForm()->getSetting('results_disabled')) {
      $configuration['settings']['update_url'] = '';
      $configuration['settings']['delete_url'] = '';
    }

    return [
      '#settings' => $configuration['settings'],
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $field_names = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('yamlform_submission'));
    $excluded_data = array_combine($field_names, $field_names);
    return [
      'type' => 'x-www-form-urlencoded',
      'insert_url' => '',
      'update_url' => '',
      'delete_url' => '',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'insert_custom_data' => '',
      'update_custom_data' => '',
      'delete_custom_data' => '',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $yamlform = $this->getYamlForm();
    $results_disabled = $yamlform->getSetting('results_disabled');

    $form['insert_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Insert URL'),
      '#description' => $this->t('The full URL to POST to when a new form submission is saved. E.g. http://www.mycrm.com/form_insert_handler.php'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['insert_url'],
    ];

    $form['update_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Update URL'),
      '#description' => $this->t('The full URL to POST to when an existing form submission is updated. E.g. http://www.mycrm.com/form_insert_handler.php'),
      '#default_value' => $this->configuration['update_url'],
      '#access' => !$results_disabled,
    ];

    $form['delete_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Save URL'),
      '#description' => $this->t('The full URL to POST to call when a form submission is deleted. E.g. http://www.mycrm.com/form_delete_handler.php'),
      '#default_value' => $this->configuration['delete_url'],
      '#access' => !$results_disabled,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML forms. You also have the option to post data in <a href="http://www.json.org/" target="_blank">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['type'],
    ];

    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    $form['submission_data']['excluded_data'] = [
      '#type' => 'yamlform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#yamlform' => $yamlform,
      '#required' => TRUE,
      '#parents' => ['settings', 'excluded_data'],
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $form['custom_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Custom data will take precedence over submission data. You may use tokens.'),
    ];

    $form['custom_data']['custom_data'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#parents' => ['settings', 'custom_data'],
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['custom_data']['insert_custom_data'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Insert data'),
      '#description' => $this->t("Enter custom data that will be included when a new form submission is saved."),
      '#parents' => ['settings', 'insert_custom_data'],
      '#states' => [
        'visible' => [
          [':input[name="settings[update_url]"]' => ['filled' => TRUE]],
          'or',
          [':input[name="settings[delete_url]"]' => ['filled' => TRUE]],
        ],
      ],
      '#default_value' => $this->configuration['insert_custom_data'],
    ];
    $form['custom_data']['update_custom_data'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Update data'),
      '#description' => $this->t("Enter custom data that will be included when a form submission is updated."),
      '#parents' => ['settings', 'update_custom_data'],
      '#states' => ['visible' => [':input[name="settings[update_url]"]' => ['filled' => TRUE]]],
      '#default_value' => $this->configuration['update_custom_data'],
    ];
    $form['custom_data']['delete_custom_data'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Delete data'),
      '#description' => $this->t("Enter custom data that will be included when a form submission is deleted."),
      '#parents' => ['settings', 'delete_custom_data'],
      '#states' => ['visible' => [':input[name="settings[delete_url]"]' => ['filled' => TRUE]]],
      '#default_value' => $this->configuration['delete_custom_data'],
    ];
    $form['custom_data']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
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
    $operation = ($update) ? 'update' : 'insert';
    $this->remotePost($operation, $yamlform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(YamlFormSubmissionInterface $yamlform_submission) {
    $this->remotePost('delete', $yamlform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $operation
   *   The type of form submission operation to be posted. Can be 'insert',
   *   'update', or 'delete'.
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   The form submission to be posted.
   */
  protected function remotePost($operation, YamlFormSubmissionInterface $yamlform_submission) {
    $request_url = $this->configuration[$operation . '_url'];
    if (empty($request_url)) {
      return;
    }

    $request_type = $this->configuration['type'];
    $request_post_data = $this->getPostData($operation, $yamlform_submission);

    try {
      switch ($request_type) {
        case 'json':
          $response = $this->httpClient->post($request_url, ['json' => $request_post_data]);
          break;

        case 'x-www-form-urlencoded':
        default:
          $response = $this->httpClient->post($request_url, ['form_params' => $request_post_data]);
          break;
      }
    }
    catch (RequestException $request_exception) {
      $message = $request_exception->getMessage();
      $response = $request_exception->getResponse();

      // If debugging is enabled, display the error message on screen.
      $this->debug($message, $operation, $request_url, $request_type, $request_post_data, $response, 'error');

      // Log error message.
      $context = [
        '@form' => $this->getYamlForm()->label(),
        '@operation' => $operation,
        '@type' => $request_type,
        '@url' => $request_url,
        '@message' => $message,
        'link' => $this->getYamlForm()->toLink(t('Edit'), 'handlers-form')->toString(),
      ];
      $this->logger->error('@form form remote @type post (@operation) to @url failed. @message', $context);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug(t('Remote post successful!'), $operation, $request_url, $request_type, $request_post_data, $response, 'warning');
  }

  /**
   * Get a form submission's post data.
   *
   * @param string $operation
   *   The type of form submission operation to be posted. Can be 'insert',
   *   'update', or 'delete'.
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   The form submission to be posted.
   *
   * @return array
   *   A form submission converted to an associative array.
   */
  protected function getPostData($operation, YamlFormSubmissionInterface $yamlform_submission) {
    // Get submission and elements data.
    $data = $yamlform_submission->toArray(TRUE);

    // Flatten data.
    // Prioritizing elements before the submissions fields.
    $data = $data['data'] + $data;
    unset($data['data']);

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $data = Yaml::decode($this->configuration['custom_data']) + $data;
    }

    // Append operation data.
    if (!empty($this->configuration[$operation . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$operation . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->tokenManager->replace($data, $yamlform_submission);

    return $data;
  }

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $operation
   *   The operation being performed, can be either insert, update, or delete.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_post_data
   *   The form submission data being posted.
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $operation, $request_url, $request_type, $request_post_data, ResponseInterface $response = NULL, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [];

    // Message.
    $build['message'] = [
      '#markup' => $message,
      '#prefix' => '<b>',
      '#suffix' => '</b>',
    ];

    // Operation.
    $build['operation'] = [
      '#type' => 'item',
      '#title' => $this->t('Remote operation'),
      '#markup' => $operation,
    ];

    // Request.
    $build['request_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Request URL'),
      '#markup' => $request_url,
    ];
    $build['request_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type'),
      '#markup' => $request_type,
    ];
    $build['request_post_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Request data'),
      'data' => [
        '#markup' => htmlspecialchars(Yaml::encode($request_post_data)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    $build['returned'] = [
      '#markup' => $this->t('...returned...'),
      '#prefix' => '<b>',
      '#suffix' => '</b>',
    ];

    // Response.
    if ($response) {
      $build['response_code'] = [
        '#type' => 'item',
        '#title' => $this->t('Response status code'),
        '#markup' => $response->getStatusCode(),
      ];
      $build['response_header'] = [
        '#type' => 'details',
        '#title' => $this->t('Response header'),
        'data' => [
          '#markup' => htmlspecialchars(Yaml::encode($response->getHeaders())),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $build['response_body'] = [
        '#type' => 'details',
        '#title' => $this->t('Response body'),
        'data' => [
          '#markup' => htmlspecialchars($response->getBody()),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
    }
    else {
      $build['response_code'] = [
        '#markup' => t('No response. Please see the recent log messages.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), $type);
  }

}
