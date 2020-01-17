<?php

namespace Drupal\sendgrid_integration\Plugin\Mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Html2Text\Html2Text;
use SendGrid\Client;
use SendGrid\Email;
use SendGrid\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Implements Drupal MailSystemInterface.
 *
 * @Mail(
 *   id = "sendgrid_integration",
 *   label = @Translation("Sendgrid Integration"),
 *   description = @Translation("Sends the message using Sendgrid API.")
 * )
 */
class SendGridMail implements MailInterface, ContainerFactoryPluginInterface {

  const SENDGRID_INTEGRATION_EMAIL_REGEX = '/^\s*(.+?)\s*<\s*([^>]+)\s*>$/';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service for the sendgrid_integration module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * SendGridMailSystem constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerChannelFactory, ModuleHandlerInterface $moduleHandler, QueueFactory $queueFactory) {
    $this->configFactory = $configFactory;
    $this->logger = $loggerChannelFactory->get('sendgrid_integration');
    $this->moduleHandler = $moduleHandler;
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('module_handler'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join message array.
    $message['body'] = implode("\n\n", $message['body']);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $site_config = $this->configFactory->get('system.site');
    $sendgrid_config = $this->configFactory->get('sendgrid_integration.settings');

    $key_secret = $sendgrid_config->get('apikey');
    if ($this->moduleHandler->moduleExists('key')) {
      $key = \Drupal::service('key.repository')->getKey($key_secret);
      if ($key) {
        $key_value = $key->getKeyValue();
        if ($key_value) {
          $key_secret = $key_value;
        }
      }
    }

    if (empty($key_secret)) {
      // Set a error in the logs if there is no API key.
      $this->logger->error('No API Secret key has been set');
      // Return false to indicate message was not able to send.
      return FALSE;
    }
    $options = [
      'turn_off_ssl_verification' => FALSE,
      'protocol' => 'https',
      'port' => NULL,
      'url' => NULL,
      'raise_exceptions' => FALSE,
    ];
    // Create a new SendGrid object.
    $client = new Client($key_secret, $options);
    $sendgrid_message = new Email();
    $sitename = $site_config->get('name');
    // Defining default unique args.
    $unique_args = [
      'id' => $message['id'],
      'module' => $message['module'],
    ];
    // If this is a password reset. Bypass spam filters.
    if (strpos($message['id'], 'password')) {
      $sendgrid_message->addFilter('bypass_list_management', 'enable', 1);
    }
    // If this is a Drupal Commerce message. Bypass spam filters.
    if (strpos($message['id'], 'commerce')) {
      $sendgrid_message->addFilter('bypass_list_management', 'enable', 1);
    }

    if (isset($message['params']['account']->uid)) {
      $unique_args['uid'] = $message['params']['account']->uid;
    }

    // Allow other modules to modify unique arguments.
    $args = $this->moduleHandler->invokeAll('sendgrid_integration_unique_args_alter', [$unique_args]);

    // Check if we got any variable back.
    if (!empty($args)) {
      $unique_args = $args;
    }

    // Checking if 'from' email-address already exist.
    if (isset($message['headers']['from']) || isset($message['headers']['From']) && $message['headers']['from'] = $message['headers']['From']) {
      $fromaddrarray = $this->parseAddress($message['headers']['from']);
      $data['from'] = $fromaddrarray[0];
      $data['fromname'] = $fromaddrarray[1];
    }
    else {
      $data['from'] = $site_config->get('mail');
      $data['fromname'] = $sitename;
    }

    // Check if $send is set to be true.
    if ($message['send'] != 1) {
      $this->logger->notice('Email was not sent because send value was disabled.');
      return TRUE;
    }
    // Build the Sendgrid mail object.
    // The message MODULE and ID is used for the Category. Category is the only
    // thing in the Sendgrid UI you can use to sort mail.
    // This is an array of categories for Sendgrid statistics.
    $categories = [
      $sitename,
      $message['module'],
      $message['id'],
    ];

    // Allow other modules to modify categories.
    $this->moduleHandler->invokeAll('sendgrid_integration_categories_alter', [
      $message,
      $categories,
    ]);

    // Check if we got any variable back.
    if (!empty($result)) {
      $categories = $result;
    }

    $sendgrid_message
      ->setFrom($data['from'])
      ->setSubject($message['subject'])
      ->setCategories($categories)
      ->setUniqueArgs($unique_args);
    if (!empty($data['fromname'])) {
      $sendgrid_message->setFromName($data['fromname']);
    }

    // If there are multiple recipients we use a different method for To:
    if (strpos($message['to'], ',')) {
      $sendtosarry = explode(',', $message['to']);
      // Don't bother putting anything in "to" and "toName" for
      // multiple addresses. Only put multiple addresses in the Smtp header.
      $sendgrid_message->setSmtpapiTos($sendtosarry);
    }
    else {
      $toaddrarray = $this->parseAddress($message['to']);
      $sendgrid_message->addTo($toaddrarray[0]);
      if (!empty($toaddrarray[1])) {
        $sendgrid_message->addToName($toaddrarray[1]);
      }
    }

    // Add cc and bcc in mail if they exist.
    $cc_bcc_keys = ['cc', 'bcc'];
    $address_cc_bcc = [];

    // Beginning of consolidated header parsing.
    foreach ($message['headers'] as $key => $value) {
      switch (Unicode::strtolower($key)) {
        case 'content-type':
          // Parse several values on the Content-type header, storing them in an array like
          // key=value -> $vars['key']='value'.
          $vars = explode(';', $value);
          foreach ($vars as $i => $var) {
            if ($cut = strpos($var, '=')) {
              $new_var = trim(Unicode::strtolower(Unicode::substr($var, $cut + 1)));
              $new_key = trim(Unicode::substr($var, 0, $cut));
              unset($vars[$i]);
              $vars[$new_key] = $new_var;
            }
          }
          // If $vars is empty then set an empty value at index 0 to avoid a PHP warning in the next statement.
          $vars[0] = isset($vars[0]) ? $vars[0] : '';
          // Nested switch to process the various content types. We only care
          // about the first entry in the array.
          switch ($vars[0]) {
            case 'text/plain':
              // The message includes only a plain text part.
              $sendgrid_message->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($message['body'])));
              break;

            case 'text/html':
              // Ensure body is a string before using it as HTML.
              $body = $message['body'];
              if ($body instanceof MarkupInterface) {
                $body = $body->__toString();
              }

              // The message includes only an HTML part.
              $sendgrid_message->setHtml($body);

              // Also include a text only version of the email.
              $converter = new Html2Text($message['body']);
              $body_plain = $converter->getText();
              $sendgrid_message->setText(MailFormatHelper::wrapMail($body_plain));
              break;

            case 'multipart/related':
              // @todo determine how to handle this content type.
              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubString($message['body'], 'boundary', '"', '"');

              break;

            case 'multipart/alternative':
              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubString($message['body'], 'boundary', '"', '"');

              // Parse text and HTML portions.
              // Split the body based on the boundary ID.
              $body_parts = $this->boundrySplit($message['body'], $boundary);
              foreach ($body_parts as $body_part) {
                // If plain/text within the body part, add it to $mailer->AltBody.
                if (strpos($body_part, 'text/plain')) {
                  // Clean up the text.
                  $body_part = trim($this->removeHeaders(trim($body_part)));
                  // Include it as part of the mail object.
                  $sendgrid_message->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($body_part)));
                }
                // If plain/html within the body part, add it to $mailer->Body.
                elseif (strpos($body_part, 'text/html')) {
                  // Clean up the text.
                  $body_part = trim($this->removeHeaders(trim($body_part)));
                  // Include it as part of the mail object.
                  $sendgrid_message->setHtml($body_part);
                }
              }
              break;

            case 'multipart/mixed':
              // Get the boundary ID from the Content-Type header.
              $boundary = $this->getSubString($value, 'boundary', '"', '"');
              // Split the body based on the boundary ID.
              $body_parts = $this->boundrySplit($message['body'], $boundary);

              // Parse text and HTML portions.
              foreach ($body_parts as $body_part) {
                if (strpos($body_part, 'multipart/alternative')) {
                  // Get the second boundary ID from the Content-Type header.
                  $boundary2 = $this->getSubString($body_part, 'boundary', '"', '"');
                  // Clean up the text.
                  $body_part = trim($this->removeHeaders(trim($body_part)));
                  // Split the body based on the internal boundary ID.
                  $body_parts2 = $this->boundrySplit($body_part, $boundary2);

                  // Process the internal parts.
                  foreach ($body_parts2 as $body_part2) {
                    // If plain/text within the body part, add it to $mailer->AltBody.
                    if (strpos($body_part2, 'text/plain')) {
                      // Clean up the text.
                      $body_part2 = trim($this->removeHeaders(trim($body_part2)));
                      $sendgrid_message->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($body_part2)));
                    }
                    // If plain/html within the body part, add it to $mailer->Body.
                    elseif (strpos($body_part2, 'text/html')) {
                      // Get the encoding.
                      $body_part2_encoding = trim($this->getSubString($body_part2, 'Content-Transfer-Encoding', ':', "\n"));
                      // Clean up the text.
                      $body_part2 = trim($this->removeHeaders(trim($body_part2)));
                      // Check whether the encoding is base64, and if so, decode it.
                      if (Unicode::strtolower($body_part2_encoding) == 'base64') {
                        // Save the decoded HTML content.
                        $sendgrid_message->setHtml(base64_decode($body_part2));
                      }
                      else {
                        // Save the HTML content.
                        $sendgrid_message->setHtml($body_part2);
                      }
                    }
                  }
                }
                else {
                  // This parses the message if there is no internal content
                  // type set after the multipart/mixed.
                  // If text/plain within the body part, add it to $mailer->Body.
                  if (strpos($body_part, 'text/plain')) {
                    // Clean up the text.
                    $body_part = trim($this->removeHeaders(trim($body_part)));
                    // Set the text message.
                    $sendgrid_message->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($body_part)));
                  }
                  // If text/html within the body part, add it to $mailer->Body.
                  elseif (strpos($body_part, 'text/html')) {
                    // Clean up the text.
                    $body_part = trim($this->removeHeaders(trim($body_part)));
                    // Set the HTML message.
                    $sendgrid_message->setHtml($body_part);
                  }
                }
              }
              break;

            default:
              // Everything else is unknown so we log and send the message as text.
              drupal_set_message(t('The %header of your message is not supported by SendGrid and will be sent as text/plain instead.', ['%header' => "Content-Type: $value"]), 'error');
              $this->logger->error("The Content-Type: $value of your message is not supported by PHPMailer and will be sent as text/plain instead.");
              // Force the email to be text.
              $sendgrid_message->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($message['body'])));
          }
          break;

        case 'reply-to':
          if (isset($message['headers']['reply-to']) || (isset($message['headers']['Reply-To']) && $message['headers']['reply-to'] = $message['headers']['Reply-To'])) {
            $sendgrid_message->setReplyTo($message['headers']['Reply-To']);
          }
          break;
      }

      // Handle latter case issue for cc and bcc key.
      if (in_array(Unicode::strtolower($key), $cc_bcc_keys)) {
        $mail_ids = explode(',', $value);
        foreach ($mail_ids as $mail_id) {
          list($mail_cc_address, $cc_name) = $this->parseAddress($mail_id);
          $address_cc_bcc[Unicode::strtolower($key)][] = [
            'mail' => $mail_cc_address,
            'name' => $cc_name,
          ];
        }
      }
    }
    if (array_key_exists('cc', $address_cc_bcc)) {
      foreach ($address_cc_bcc['cc'] as $item) {
        $sendgrid_message->addCc($item['mail']);
        $sendgrid_message->addCcName($item['name']);
      }
    }
    if (array_key_exists('bcc', $address_cc_bcc)) {
      foreach ($address_cc_bcc['bcc'] as $item) {
        $sendgrid_message->addBcc($item['mail']);
        $sendgrid_message->addBccName($item['name']);
      }
    }

    // Prepare attachments.
    $attachments = [];
    if (isset($message['attachments']) && !empty($message['attachments'])) {
      foreach ($message['attachments'] as $attachmentitem) {
        if (is_file($attachmentitem)) {
          $attachments[$attachmentitem] = $attachmentitem;
        }
      }
    }

    // If we have attachments, add them.
    if (!empty($attachments)) {
      $sendgrid_message->setAttachments($attachments);
    }

    // Add template ID.
    if (isset($message['sendgrid']['template_id'])) {
      $sendgrid_message->setTemplateId($message['sendgrid']['template_id']);
    }

    // Add substitutions.
    if (isset($message['sendgrid']['substitutions'])) {
      $sendgrid_message->setSubstitutions($message['sendgrid']['substitutions']);
    }

    // Lets try and send the message and catch the error.
    try {
      $response = $client->send($sendgrid_message);
    }
    catch (Exception $e) {
      $this->logger->error('Sending emails to Sengrind service failed with error code ' . $e->getCode());
      foreach ($e->getErrors() as $error_info) {
        $this->logger->error('Sendgrid generated error ' . $error_info);
      }
      // Add message to queue if reason for failing was timeout or
      // another valid reason. This adds more error tolerance.
      $codes = [
        -110,
        404,
        408,
        500,
        502,
        503,
        504,
      ];
      if (in_array($e->getCode(), $codes)) {
        $this->queueFactory->get('SendGridResendQueue')->createItem($message);
      }
      return FALSE;
    }
    // Creating hook, allowing other modules react on sent email.
    $hook_args = [$message['to'], $unique_args, $response];
    $this->moduleHandler->invokeAll('sendgrid_integration_sent', $hook_args);

    if ($response->getCode() == 200) {
      // If the code is 200 we are good to finish and proceed.
      return TRUE;
    }
    // Default to low. Sending failed.
    return FALSE;
  }

  /**
   * Returns a string that is contained within another string.
   *
   * Returns the string from within $source that is some where after $target
   * and is between $beginning_character and $ending_character.
   *
   * Swiped from SMTP module. Thanks!
   *
   * @param string $source
   *   A string containing the text to look through.
   * @param string $target
   *   A string containing the text in $source to start looking from.
   * @param string $beginning_character
   *   A string containing the character just before the sought after text.
   * @param string $ending_character
   *   A string containing the character just after the sought after text.
   *
   * @return string
   *   A string with the text found between the $beginning_character and the
   *   $ending_character.
   */
  protected function getSubString($source, $target, $beginning_character, $ending_character) {
    $search_start = strpos($source, $target) + 1;
    $first_character = strpos($source, $beginning_character, $search_start) + 1;
    $second_character = strpos($source, $ending_character, $first_character) + 1;
    $substring = Unicode::substr($source, $first_character, $second_character - $first_character);
    $string_length = Unicode::strlen($substring) - 1;

    if ($substring[$string_length] == $ending_character) {
      $substring = Unicode::substr($substring, 0, $string_length);
    }

    return $substring;
  }

  /**
   * Splits the input into parts based on the given boundary.
   *
   * Swiped from Mail::MimeDecode, with modifications based on Drupal's coding
   * standards and this bug report: http://pear.php.net/bugs/bug.php?id=6495
   *
   * @param string $input
   *   A string containing the body text to parse.
   * @param string $boundary
   *   A string with the boundary string to parse on.
   *
   * @return array
   *   An array containing the resulting mime parts
   */
  protected function boundrySplit($input, $boundary) {
    $parts = [];
    $bs_possible = Unicode::substr($boundary, 2, -2);
    $bs_check = '\"' . $bs_possible . '\"';

    if ($boundary == $bs_check) {
      $boundary = $bs_possible;
    }

    $tmp = explode('--' . $boundary, $input);

    for ($i = 1; $i < count($tmp); $i++) {
      if (trim($tmp[$i])) {
        $parts[] = $tmp[$i];
      }
    }

    return $parts;
  }

  /**
   * Strips the headers from the body part.
   *
   * @param string $input
   *   A string containing the body part to strip.
   *
   * @return string
   *   A string with the stripped body part.
   */
  protected function removeHeaders($input) {
    $part_array = explode("\n", $input);

    // Will strip these headers according to RFC2045.
    $headers_to_strip = [
      'Content-Type',
      'Content-Transfer-Encoding',
      'Content-ID',
      'Content-Disposition',
    ];
    $pattern = '/^(' . implode('|', $headers_to_strip) . '):/';

    while (count($part_array) > 0) {

      // Ignore trailing spaces/newlines.
      $line = rtrim($part_array[0]);

      // If the line starts with a known header string.
      if (preg_match($pattern, $line)) {
        $line = rtrim(array_shift($part_array));
        // Remove line containing matched header.
        // If line ends in a ';' and the next line starts with four spaces, it's a continuation
        // of the header split onto the next line. Continue removing lines while we have this condition.
        while (substr($line, -1) == ';' && count($part_array) > 0 && substr($part_array[0], 0, 4) == '    ') {
          $line = rtrim(array_shift($part_array));
        }
      }
      else {
        // No match header, must be past headers; stop searching.
        break;
      }
    }

    $output = implode("\n", $part_array);
    return $output;
  }

  /**
   * Split an email address into it's name and address components.
   */
  protected function parseAddress($email) {
    if (preg_match(self::SENDGRID_INTEGRATION_EMAIL_REGEX, $email, $matches)) {
      return [$matches[2], $matches[1]];
    }
    else {
      return [$email, ' '];
    }
  }

}
