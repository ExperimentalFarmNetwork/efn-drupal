<?php

namespace Drupal\yamlform\Tests;

use Drupal\file\Entity\File;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Entity\YamlFormSubmission;

/**
 * Test for form element managed file handling.
 *
 * @group YamlForm
 */
class YamlFormElementManagedFileTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'block', 'file', 'user', 'yamlform', 'yamlform_test'];

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The 'test_element_managed_file' form.
   *
   * @var \Drupal\yamlform\YamlFormInterface
   */
  protected $yamlform;

  /**
   * An array of plain text test files.
   *
   * @var array
   */
  protected $files;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileUsage = $this->container->get('file.usage');
    $this->yamlform = YamlForm::load('test_element_managed_file');
    $this->files = $this->drupalGetTestFiles('text');

    $this->verbose('<pre>' . print_r($this->files, TRUE) . '</pre>');
  }

  /**
   * Test private files.
   */
  public function testPrivateFiles() {
    $elements = $this->yamlform->getElementsDecoded();
    $elements['managed_file_single']['#uri_scheme'] = 'private';
    $this->yamlform->setElements($elements);
    $this->yamlform->save();

    $this->drupalLogin($this->adminSubmissionUser);

    // Upload private file.
    $edit = [
      'files[managed_file_single]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
    ];
    $sid = $this->postSubmission($this->yamlform, $edit);

    /** @var \Drupal\yamlform\YamlFormSubmissionInterface $submission */
    $submission = YamlFormSubmission::load($sid);

    /** @var \Drupal\file\Entity\File $file */
    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check that test file 3 was uploaded to the current submission.
    $this->assertEqual($submission->getData('managed_file_single'), $fid, 'Test file 3 was upload to the current submission');

    // Check test file 3 file usage.
    $this->assertIdentical(['yamlform' => ['yamlform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 3 usage.');

    // Check test file 3 uploaded file path.
    $this->assertEqual($file->getFileUri(), 'private://yamlform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename);

    // Check private file access allowed.
    $this->drupalGet(file_create_url($file->getFileUri()));
    $this->assertResponse(200);

    $this->drupalLogout();

    // Check private file access denied.
    $this->drupalGet(file_create_url($file->getFileUri()));
    $this->assertResponse(403);
  }

  /**
   * Test single and multiple file upload.
   */
  public function testFileUpload() {
    $this->checkFileUpload('single', $this->files[0], $this->files[1]);
    $this->checkFileUpload('multiple', $this->files[2], $this->files[3]);
  }

  /**
   * Test media file upload elements.
   */
  public function testMediaFileUpload() {
    global $base_url;

    /* Element processing */

    // Get test form.
    $this->drupalGet('yamlform/test_element_media_file');

    // Check document file.
    $this->assertRaw('<input data-drupal-selector="edit-document-file-upload" type="file" id="edit-document-file-upload" name="files[document_file]" size="22" class="js-form-file form-file" />');

    // Check audio file.
    $this->assertRaw('<input data-drupal-selector="edit-audio-file-upload" accept="audio/*" capture type="file" id="edit-audio-file-upload" name="files[audio_file]" size="22" class="js-form-file form-file" />');

    // Check image file.
    $this->assertRaw('<input data-drupal-selector="edit-image-file-upload" accept="image/*" capture type="file" id="edit-image-file-upload" name="files[image_file]" size="22" class="js-form-file form-file" />');

    // Check video file.
    $this->assertRaw('<input data-drupal-selector="edit-video-file-upload" accept="video/*" capture type="file" id="edit-video-file-upload" name="files[video_file]" size="22" class="js-form-file form-file" />');

    /* Element rendering */

    // Get test form preview with test values.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalPostForm('yamlform/test_element_media_file/test', [], t('Preview'));

    // Check audio file preview.
    $this->assertRaw('<source src="' . $base_url . '/system/files/yamlform/test_element_media_file/_sid_/audio_file_mp3.mp3" type="audio/mpeg">');

    // Check image file preview.
    $this->assertRaw('<img src="' . $base_url . '/system/files/yamlform/test_element_media_file/_sid_/image_file_jpg.jpg" class="yamlform-image-file" />');

    // Check video file preview.
    $this->assertRaw('<source src="' . $base_url . '/system/files/yamlform/test_element_media_file/_sid_/video_file_mp4.mp4" type="video/mp4">');
  }

  /**
   * Check file upload.
   *
   * @param string $type
   *   The type of file upload which can be either single or multiple.
   * @param object $first_file
   *   The first file to be uploaded.
   * @param object $second_file
   *   The second file that replaces the first file.
   */
  protected function checkFileUpload($type, $first_file, $second_file) {
    $key = 'managed_file_' . $type;
    $parameter_name = ($type == 'multiple') ? "files[$key][]" : "files[$key]";

    $edit = [
      $parameter_name => \Drupal::service('file_system')->realpath($first_file->uri),
    ];
    $sid = $this->postSubmission($this->yamlform, $edit);

    /** @var \Drupal\yamlform\YamlFormSubmissionInterface $submission */
    $submission = YamlFormSubmission::load($sid);

    /** @var \Drupal\file\Entity\File $file */
    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check that test file was uploaded to the current submission.
    $second = ($type == 'multiple') ? [$fid] : $fid;
    $this->assertEqual($submission->getData($key), $second, 'Test file was upload to the current submission');

    // Check test file file usage.
    $this->assertIdentical(['yamlform' => ['yamlform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 1 usage.');

    // Check test file uploaded file path.
    $this->assertEqual($file->getFileUri(), 'private://yamlform/test_element_managed_file/' . $sid . '/' . $first_file->filename);

    // Check that test file exists.
    $this->assert(file_exists($file->getFileUri()), 'File exists');

    // Login admin user.
    $this->drupalLogin($this->adminSubmissionUser);

    // Check managed file formatting.
    $this->drupalGet('/admin/structure/yamlform/manage/test_element_managed_file/submission/' . $sid);
    if ($type == 'multiple') {
      $this->assertRaw('<b>managed_file (multiple)</b><br/><div class="item-list"><ul><li>');
    }
    $this->assertRaw('<span class="file file--mime-text-plain file--text"> <a href="' . file_create_url($file->getFileUri()) . '" type="text/plain; length=' . $file->getSize() . '">' . $file->getFilename() . '</a></span>');

    // Remove the uploaded file.
    if ($type == 'multiple') {
      $edit = ['managed_file_multiple[file_' . $fid . '][selected]' => TRUE];
      $submit = t('Remove selected');
    }
    else {
      $edit = [];
      $submit = t('Remove');
    }
    $this->drupalPostForm('/admin/structure/yamlform/manage/test_element_managed_file/submission/' . $sid . '/edit', $edit, $submit);

    // Upload new file.
    $edit = [
      $parameter_name => \Drupal::service('file_system')->realpath($second_file->uri),
    ];
    $this->drupalPostForm(NULL, $edit, t('Upload'));

    // Submit the new file.
    $this->drupalPostForm(NULL, [], t('Save'));

    /** @var \Drupal\file\Entity\File $test_file_0 */
    $new_fid = $this->getLastFileId();
    $new_file = File::load($new_fid);

    \Drupal::entityTypeManager()->getStorage('yamlform_submission')->resetCache();
    $submission = YamlFormSubmission::load($sid);

    // Check that test new file was uploaded to the current submission.
    $second = ($type == 'multiple') ? [$new_fid] : $new_fid;
    $this->assertEqual($submission->getData($key), $second, 'Test new file was upload to the current submission');

    // Check that test file was deleted from the disk and database.
    $this->assert(!file_exists($file->getFileUri()), 'Test file deleted from disk');
    $this->assertEqual(0, db_query('SELECT COUNT(fid) AS total FROM {file_managed} WHERE fid=:fid', [':fid' => $fid])->fetchField(), 'Test file 0 deleted from database');
    $this->assertEqual(0, db_query('SELECT COUNT(fid) AS total FROM {file_usage} WHERE fid=:fid', [':fid' => $fid])->fetchField(), 'Test file 0 deleted from database');

    // Check test file 1 file usage.
    $this->assertIdentical(['yamlform' => ['yamlform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($new_file), 'The new file has 1 usage.');

    // Delete the submission.
    $submission->delete();

    // Check that test file 1 was deleted from the disk and database.
    $this->assert(!file_exists($new_file->getFileUri()), 'Test new file deleted from disk');
    $this->assertEqual(0, db_query('SELECT COUNT(fid) AS total FROM {file_managed} WHERE fid=:fid', [':fid' => $new_fid])->fetchField(), 'Test new file deleted from database');
  }

  /****************************************************************************/
  // Helper functions. From: \Drupal\file\Tests\FileFieldTestBase::getTestFile
  /****************************************************************************/

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

}
