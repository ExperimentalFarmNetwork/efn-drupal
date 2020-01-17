<?php

namespace Drupal\sendgrid_integration\Plugin\QueueWorker;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sendgrid resend queue worker.
 *
 * @QueueWorker(
 *   id = "SendGridResendQueue",
 *   title = @Translation("SendGrid Resend queue"),
 *   cron = {"time" = 60}
 * )
 */
class SendgridQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * SendgridQueue constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   THe mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mailManager) {
    $this->mailManager = $mailManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($message) {
    // Defining parameters first to improve code readability.
    $module = $message['module'];
    $key = $message['key'];
    $to = $message['to'];
    $language = $message['language'];
    $params = $message['params'];
    $from = $message['from'];
    $send = $message['send'];

    $this->mailManager->mail($module, $key, $to, $language, $params, $from, $send);
  }

}
