<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
// ISSUE: Below import statement is throwing "Error: Cannot use Drupal\Core\Url
// as Url because the name is already in use in" when executing any drush
// yamlform command that loads this file.
// use Drupal\Core\Url.
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\file\Element\ManagedFile as ManagedFileElement;
use Drupal\yamlform\Entity\YamlFormSubmission;
use Drupal\yamlform\YamlFormElementBase;
use Drupal\Component\Utility\Bytes;
use Drupal\yamlform\YamlFormInterface;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a base class form 'managed_file' elements.
 */
abstract class YamlFormManagedFileBase extends YamlFormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $max_filesize = \Drupal::config('yamlform.settings')->get('file.default_max_filesize') ?: file_upload_max_size();
    $max_filesize = Bytes::toInt($max_filesize);
    $max_filesize = ($max_filesize / 1024 / 1024);
    $file_extensions = $this->getFileExtensions();
    return parent::getDefaultProperties() + [
      'multiple' => FALSE,
      'max_filesize' => $max_filesize,
      'file_extensions' => $file_extensions,
      'uri_scheme' => 'private',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return (!empty($element['#multiple'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    if ($this->hasMultipleValues($element)) {
      return TRUE;
    }
    else {
      return parent::isMultiline($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (!parent::isEnabled()) {
      return FALSE;
    }

    // Disable managed file element is there are no visible stream wrappers.
    $scheme_options = self::getVisibleStreamWrappers();
    return (empty($scheme_options)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    // Display standard disabled element warning.
    if (!parent::isEnabled()) {
      parent::displayDisabledWarning($element);
    }
    else {
      // Display 'managed_file' stream wrappers warning.
      $scheme_options = self::getVisibleStreamWrappers();
      $uri_scheme = $this->getUriScheme($element);
      if (!isset($scheme_options[$uri_scheme]) && $this->currentUser->hasPermission('administer yamlform')) {
        drupal_set_message($this->t('The \'Managed file\' element is unavailable because a <a href="https://www.drupal.org/documentation/modules/file">private files directory</a> has not been configured and public file uploads have not been enabled. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'), 'warning');
        $context = [
          'link' => Link::fromTextAndUrl($this->t('Edit'), \Drupal\Core\Url::fromRoute('<current>'))->toString(),
        ];
        $this->logger->notice("The 'Managed file' element is unavailable because no stream wrappers are available", $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);

    // Check if the URI scheme exists and can be used the upload location.
    $scheme_options = self::getVisibleStreamWrappers();
    $uri_scheme = $this->getUriScheme($element);
    if (!isset($scheme_options[$uri_scheme])) {
      $element['#access'] = FALSE;
      $this->displayDisabledWarning($element);
    }
    else {
      $element['#upload_location'] = $this->getUploadLocation($element, $yamlform_submission->getYamlForm());
    }

    $element['#upload_validators']['file_validate_size'] = [$this->getMaxFileSize($element)];
    $element['#upload_validators']['file_validate_extensions'] = [$this->getFileExtensions($element)];

    // Use custom validation callback so that File entities can be converted
    // into file ids (akk fids).
    $element['#element_validate'][] = [get_class($this), 'validate'];

    // Add file upload help to the element.
    $element['help'] = [
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $element['#upload_validators'],
      '#cardinality' => (empty($element['#multiple'])) ? 1 : -1,
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWrapper(array &$element) {
    parent::prepareWrapper($element);

    // Issue #2705471: Form states managed file fields.
    // Workaround: Wrap the 'managed_file' element in a basic container.
    if (!empty($element['#fixed_wrapper']) || empty($element['#prefix'])) {
      return;
    }

    $container = [
      '#prefix' => $element['#prefix'],
      '#suffix' => $element['#suffix'],
    ];
    unset($element['#prefix'], $element['#suffix']);
    $container[$element['#yamlform_key']] = $element + ['#fixed_wrapper' => TRUE];
    $element = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (!empty($element['#default_value']) && !is_array($element['#default_value'])) {
      $element['#default_value'] = [$element['#default_value']];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array &$element, $value, array $options = []) {
    if (empty($value)) {
      return '';
    }

    $items = $this->formatItems($element, $value, $options);
    if (empty($items)) {
      return '';
    }

    if ($this->hasMultipleValues($element)) {
      return [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }
    else {
      return reset($items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array &$element, $value, array $options = []) {
    if (empty($value)) {
      return '';
    }

    if (empty($element['#format']) || $element['#format'] == 'link') {
      $element['#format'] = 'url';
    }

    $items = $this->formatItems($element, $value, $options);
    if (empty($items)) {
      return '';
    }

    // Add dash (aka bullet) before each item.
    if ($this->hasMultipleValues($element)) {
      foreach ($items as &$item) {
        $item = '- ' . $item;
      }
    }

    return implode("\n", $items);
  }

  /**
   * Format a managed files as array of strings.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   Managed files as array of strings.
   */
  protected function formatItems(array &$element, $value, array $options) {
    $fids = (is_array($value)) ? $value : [$value];

    $files = File::loadMultiple($fids);
    $format = $this->getFormat($element);
    $items = [];
    foreach ($files as $fid => $file) {
      switch ($format) {
        case 'link':
          $items[$fid] = [
            '#theme' => 'file_link',
            '#file' => $file,
          ];
          break;

        case 'id':
          $items[$fid] = $file->id();
          break;

        case 'url':
        case 'value':
        case 'raw':
          $items[$fid] = file_create_url($file->getFileUri());
          break;

        default:
          $theme = str_replace('yamlform_', 'yamlform_element_', $this->getPluginId());
          if (strpos($theme, 'yamlform_') !== 0) {
            $theme = 'yamlform_element_' . $theme;
          }
          $items[$fid] = [
            '#theme' => $theme,
            '#element' => $element,
            '#value' => $value,
            '#options' => $options,
            '#file' => $file,
          ];
          break;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    return parent::getFormats() + [
      'file' => $this->t('File'),
      'link' => $this->t('Link'),
      'id' => $this->t('File ID'),
      'url' => $this->t('URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#yamlform_key'];
    return [":input[name=\"files[{$name}]\"]" => $title . '  [' . $this->getPluginLabel() . ']'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, YamlFormSubmissionInterface $yamlform_submission, $update = TRUE) {
    // Get current value and original value for this element.
    $key = $element['#yamlform_key'];

    $original_data = $yamlform_submission->getOriginalData();
    $data = $yamlform_submission->getData();

    $value = isset($data[$key]) ? $data[$key] : [];
    $fids = (is_array($value)) ? $value : [$value];

    $original_value = isset($original_data[$key]) ? $original_data[$key] : [];
    $original_fids = (is_array($original_value)) ? $original_value : [$original_value];

    // Check the original submission fids and delete the old file upload.
    foreach ($original_fids as $original_fid) {
      if (!in_array($original_fid, $fids)) {
        file_delete($original_fid);
      }
    }

    // Exit if there is no fids.
    if (empty($fids)) {
      return;
    }

    $files = File::loadMultiple($fids);
    foreach ($files as $file) {
      $source_uri = $file->getFileUri();

      // Replace /_sid_/ token with the submission id.
      if (strpos($source_uri, '/_sid_/')) {
        $destination_uri = str_replace('/_sid_/', '/' . $yamlform_submission->id() . '/', $source_uri);
        $destination_directory = \Drupal::service('file_system')->dirname($destination_uri);
        file_prepare_directory($destination_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        $destination_uri = file_unmanaged_move($source_uri, $destination_uri);
        // Update the file's uri and save.
        $file->setFileUri($destination_uri);
        $file->save();
      }

      // Update file usage table.
      // Set file usage which will also make the file's status permanent.
      /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
      $file_usage = \Drupal::service('file.usage');
      $file_usage->delete($file, 'yamlform', 'yamlform_submission', $yamlform_submission->id(), 0);
      $file_usage->add($file, 'yamlform', 'yamlform_submission', $yamlform_submission->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    $yamlform = $yamlform_submission->getYamlForm();

    $data = $yamlform_submission->getData();
    $key = $element['#yamlform_key'];

    $value = isset($data[$key]) ? $data[$key] : [];
    $fids = (is_array($value)) ? $value : [$value];

    // Delete managed file record.
    foreach ($fids as $fid) {
      file_delete($fid);
    }

    // Remove the empty directory for all stream wrappers.
    $stream_wrappers = array_keys(\Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE));
    foreach ($stream_wrappers as $stream_wrapper) {
      file_unmanaged_delete_recursive($stream_wrapper . '://yamlform/' . $yamlform->id() . '/' . $yamlform_submission->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValue(array $element, YamlFormInterface $yamlform) {
    if ($this->isDisabled()) {
      return NULL;
    }

    $file_extensions = explode(' ', $this->getFileExtensions($element));
    $file_extension = $file_extensions[array_rand($file_extensions)];
    $upload_location = $this->getUploadLocation($element, $yamlform);
    $file_destination = $upload_location . '/' . $element['#yamlform_key'] . '.' . $file_extension;

    // Look for an existing temp files that have not been uploaded.
    $fids = \Drupal::entityQuery('file')
      ->condition('status', 0)
      ->condition('uid', \Drupal::currentUser()->id())
      ->condition('uri', $upload_location . '/' . $element['#yamlform_key'] . '.%', 'LIKE')
      ->execute();
    if ($fids) {
      return reset($fids);
    }

    // Copy sample file or generate a new temp file that can be uploaded.
    $sample_file = drupal_get_path('module', 'yamlform') . '/tests/files/sample.' . $file_extension;
    if (file_exists($sample_file)) {
      $file_uri = file_unmanaged_copy($sample_file, $file_destination);
    }
    else {
      $file_uri = file_unmanaged_save_data('{empty}', $file_destination);
    }

    $file = File::create([
      'uri' => $file_uri ,
      'uid' => \Drupal::currentUser()->id(),
    ]);
    $file->save();

    $fid = $file->id();
    return ($this->hasMultipleValues($element)) ? [$fid] : $fid;
  }

  /**
   * Get max file size for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   Max file size.
   */
  protected function getMaxFileSize(array $element) {
    // Set max file size.
    $max_filesize = \Drupal::config('yamlform.settings')->get('file.default_max_filesize') ?: file_upload_max_size();
    $max_filesize = Bytes::toInt($max_filesize);
    if (!empty($element['#max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($element['#max_filesize']) * 1024 * 1024);
    }
    return $max_filesize;
  }

  /**
   * Get allowed file extensions for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   File extension.
   */
  protected function getFileExtensions(array $element = NULL) {
    $file_type = str_replace('yamlform_', '', $this->getPluginId());

    // Set valid file extensions.
    $file_extensions = \Drupal::config('yamlform.settings')->get("file.default_{$file_type}_extensions");
    if (!empty($element['#file_extensions'])) {
      $file_extensions = $element['#file_extensions'];
    }
    return $file_extensions;
  }

  /**
   * Get file upload location.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   *
   * @return string
   *   Upload location.
   */
  protected function getUploadLocation(array $element, YamlFormInterface $yamlform) {
    if (empty($element['#upload_location'])) {
      $upload_location = $this->getUriScheme($element) . '://yamlform/' . $yamlform->id() . '/_sid_';
    }
    else {
      $upload_location = $element['#upload_location'];
    }

    // Make sure the upload location exists and is writable.
    file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    return $upload_location;
  }

  /**
   * Get file upload URI scheme.
   *
   * Defaults to private file uploads.
   *
   * Drupal file upload by anonymous or untrusted users into public file systems
   * -- PSA-2016-003.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   File upload URI scheme.
   *
   * @see https://www.drupal.org/psa-2016-003
   */
  protected function getUriScheme(array $element) {
    if (isset($element['#uri_scheme'])) {
      return $element['#uri_scheme'];
    }
    $scheme_options = self::getVisibleStreamWrappers();
    if (isset($scheme_options['private'])) {
      return 'private';
    }
    elseif (isset($scheme_options['public'])) {
      return 'public';
    }
    else {
      return 'private';
    }
  }

  /**
   * Form API callback. Consolidate the array of fids for this field into a single fids.
   */
  public static function validate(array &$element, FormStateInterface $form_state, &$complete_form) {
    // Call the default managed_element validation handler, which checks
    // the file entity and #required.
    // @see \Drupal\file\Element\ManagedFile::getInfo
    // @see \Drupal\yamlform\Plugin\YamlFormElement\ManagedFile::prepare
    ManagedFileElement::validateManagedFile($element, $form_state, $complete_form);

    if (!empty($element['#files'])) {
      $fids = array_keys($element['#files']);
      if (empty($element['#multiple'])) {
        $form_state->setValueForElement($element, reset($fids));
      }
      else {
        $form_state->setValueForElement($element, $fids);
      }
    }
    else {
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['file'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File settings'),
    ];
    $scheme_options = self::getVisibleStreamWrappers();

    $form['file']['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#description' => t('Select where the final files should be stored. Private file storage has more overhead than public files, but allows restricted access to files within this element.'),
      '#required' => TRUE,
      '#options' => $scheme_options,
    ];
    // Public files security warning.
    if (isset($scheme_options['public'])) {
      $form['file']['uri_public_warning'] = [
        '#type' => 'yamlform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Public files upload destination is dangerous for forms that are available to anonymous and/or untrusted users.') . ' ' .
          $this->t('For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[uri_scheme]"]' => ['value' => 'public'],
          ],
        ],
      ];
    }
    // Private files not set warning.
    if (!isset($scheme_options['private'])) {
      $form['file']['uri_private_warning'] = [
        '#type' => 'yamlform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Private file system is not set. This must be changed in <a href="https://www.drupal.org/documentation/modules/file">settings.php</a>. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
      ];
    }

    $form['file']['max_filesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum file size'),
      '#field_suffix' => $this->t('MB'),
      '#description' => $this->t('Enter the max file size a user may upload.'),
      '#min' => 1,
    ];
    $form['file']['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File extensions'),
      '#description' => $this->t('A list of additional file extensions for this upload field, separated by spaces.'),
      '#maxlength' => 255,
    ];
    $form['file']['multiple'] = [
      '#title' => $this->t('Multiple'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#description' => $this->t('Check this option if the user should be allowed to upload multiple files.'),
    ];
    return $form;
  }

  /**
   * Control access to form submission private file downloads.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return mixed
   *   Returns NULL is the file is not attached to a form submission.
   *   Returns -1 if the user does not have permission to access a form.
   *   Returns an associative array of headers.
   *
   * @see hook_file_download()
   * @see yamlform_file_download()
   */
  public static function accessFileDownload($uri) {
    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    if (empty($files)) {
      return NULL;
    }

    $file = reset($files);
    if (empty($file)) {
      return NULL;
    }

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $usage = $file_usage->listUsage($file);
    foreach ($usage as $module => $entity_types) {
      // Check for YAML Form module.
      if ($module != 'yamlform') {
        continue;
      }

      foreach ($entity_types as $entity_type => $counts) {
        $entity_ids = array_keys($counts);

        // Check for form submission entity type.
        if ($entity_type != 'yamlform_submission' || empty($entity_ids)) {
          continue;
        }

        // Get form submission.
        $yamlform_submission = YamlFormSubmission::load(reset($entity_ids));
        if (!$yamlform_submission) {
          continue;
        }

        // Check form submission view access.
        if (!$yamlform_submission->access('view')) {
          return -1;
        }

        // Return file content headers.
        return file_get_content_headers($file);
      }
    }
    return NULL;
  }

  /**
   * Get visible stream wrappers.
   *
   * @return array
   *   An associative array of visible stream wrappers keyed by type.
   */
  public static function getVisibleStreamWrappers() {
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    if (!\Drupal::config('yamlform.settings')->get('file.file_public')) {
      unset($stream_wrappers['public']);
    }
    return $stream_wrappers;
  }

}
