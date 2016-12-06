<?php

namespace Drupal\yamlform\Tests;

use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for remote post form handler functionality.
 *
 * @group YamlForm
 */
class YamlFormHandlerRemotePostTest extends YamlFormTestBase {

  /**
   * Test remote post handler.
   */
  public function testRemotePostHandler() {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform_handler_remote */
    $yamlform_handler_remote = YamlForm::load('test_handler_remote_post');

    $this->drupalLogin($this->adminFormUser);

    // Check remote post 'create' operation.
    $sid = $this->postSubmission($yamlform_handler_remote);
    $this->assertPattern('#<label>Remote operation</label>\s+insert#ms');

    // Check remote post 'update' operation.
    $this->drupalPostForm("admin/structure/yamlform/manage/test_handler_remote_post/submission/$sid/edit", [], t('Save'));
    $this->assertPattern('#<label>Remote operation</label>\s+update#ms');

    // Check remote post 'delete' operation.
    $this->drupalPostForm("admin/structure/yamlform/manage/test_handler_remote_post/submission/$sid/delete", [], t('Delete'));
    $this->assertPattern('#<label>Remote operation</label>\s+delete#ms');

    // @todo Figure out why the below test is failing on Drupal.org.
    // Check remote post 'create' 500 error handling.
    // $this->postSubmission($yamlform_handler_remote, ['first_name' => 'FAIL']);
    // $this->assertPattern('#<label>Response status code</label>\s+500#ms');

    // @todo Figure out why the below test is failing on Drupal.org.
    // Update the remote post handlers insert url to return a 404 error.
    // /** @var \Drupal\yamlform\Plugin\YamlFormHandler\RemotePostYamlFormHandler $handler */
    // $handler = $yamlform_handler_remote->getHandler('remote_post');
    // $configuration = $handler->getConfiguration();
    // $configuration['settings']['insert_url'] .= '/broken';
    // $handler->setConfiguration($configuration);
    // $yamlform_handler_remote->save();

    // $this->postSubmission($yamlform_handler_remote, ['first_name' => 'FAIL']);
    // $this->assertPattern('#<label>Response status code</label>\s+404#ms');
  }

}
