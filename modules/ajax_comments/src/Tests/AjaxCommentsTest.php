<?php

namespace Drupal\ajax_comments\Tests;

use Drupal\ajax_comments\Controller\AjaxCommentsController;
use Drupal\comment\Tests\CommentTestBase;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

/**
 * Tests posting ajax comments.
 *
 * @group ajax_comments
 */
class AjaxCommentsTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'block',
    'comment',
    'node',
    'ajax_comments',
  ];

  /**
   * Tests posting a comment using ajax.
   */
  public function testAjaxCommentPost() {
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->node->id());
    $comment_text = $this->randomMachineName();
    $edit = [
      'comment_body[0][value]'  => $comment_text,
    ];
    $ajax_result = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Save')]);
    // Loop through the responses to find the replacement command.
    foreach ($ajax_result as $index => $command) {
      if ($command['command'] === 'insert' && $command['method'] === 'replaceWith') {
        $this->setRawContent($command['data']);
        $this->pass('Ajax replacement content: ' . $command['data']);
      }
    }
    $this->assertText($comment_text, 'Comment posted.');
    $this->pass('Comment: ' . $comment_text);

    // Test loading the delete confirmation form as a modal dialog.
    // Need to log in as adminUser to delete comments.
    $this->drupalLogin($this->adminUser);
    // Need to reload the node to get the updated comment field values.
    $node = Node::load($this->node->id());

    $comment_cid = $node->get('comment')->get(0)->cid;
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = Comment::load($comment_cid);

    $delete_form_render_array = \Drupal::service('entity.form_builder')
      ->getForm($comment, 'delete', ['input' => ['_drupal_ajax' => TRUE]]);
    $delete_form_rendered = \Drupal::service('renderer')->renderRoot($delete_form_render_array);

    $delete_form_expected_response = [
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'data' => $delete_form_rendered,
      'dialogOptions' => [
        'modal' => TRUE,
        'title' => t(
          'Are you sure you want to delete the comment @label?',
          ['@label' => $comment->label()]
        )->render(),
      ],
    ];

    // Test opening a modal dialog with the comment delete confirmation form.
    $modal_form_response = $this->drupalGetAjax(
      'comment/' . $comment->id() . '/delete',
      ['query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal')]
    );
    // The openDialog command appears alternately at $modal_form_response[1]
    // and $modal_form_response[2], so loop through to check each.
    $delete_form_response_found = FALSE;
    foreach ($modal_form_response as $index => $command) {
      if ($command['command'] === 'openDialog') {
        // Strip tags from the rendered content because the form build ID and
        // token will never match.
        $delete_form_expected_response['data'] = trim(Xss::filter(
          $delete_form_expected_response['data'],
          []
        ));
        $command['data'] = trim(Xss::filter(
          $command['data'],
          []
        ));
        // The response arrays should now be identical.
        if ($delete_form_expected_response === $command) {
          $delete_form_response_found = TRUE;
        }
        break;
      }
    }
    $this->assertTrue(
      $delete_form_response_found,
      'Form content matches'
    );

    // Test actually deleting the comment.
    $ajax_settings = $delete_form_render_array['actions']['submit']['#ajax'];
    $ajax_settings['url'] = $ajax_settings['url']->toString();
    $ajax_result = $this->drupalPostAjaxForm(
      // Path.
      'comment/' . $comment->id() . '/delete',
      // Edit.
      ['confirm' => 1],
      // Triggering element.
      ['op' => t('Delete')],
      // Ajax path.
      'ajax_comments/' . $comment->id() . '/delete',
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      $ajax_settings
    );
    foreach ($ajax_result as $index => $command) {
      if ($command['command'] === 'insert' && $command['method'] === 'replaceWith') {
        $this->setRawContent($command['data']);
      }
    }
    $this->assertNoText($comment_text, 'Comment removed from the page.');

    // Test ajax comment edit functionality.
    // webUser has the permission 'edit own comments'.
    $this->drupalLogin($this->webUser);

    // Create a new comment to test the edit functionality.
    $this->drupalGet('node/' . $this->node->id());
    $comment_text_2 = $this->randomMachineName();
    $edit = [
      'comment_body[0][value]'  => $comment_text_2,
    ];
    $ajax_result = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Save')]);
    // The second ajax command ($ajax_result[1]) is the replacement one.
    $this->pass('Second comment submitted: ' . $comment_text_2);
    $this->pass('Second comment returned: ' . $ajax_result[1]['data']);

    // Need to reload the node to get the updated comment field values.
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache();
    $node = Node::load($this->node->id());
    $comment_cid = $node->get('comment')->get(0)->cid;
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = Comment::load($comment_cid);

    $edit_form_render_array = \Drupal::service('entity.form_builder')
      ->getForm($comment, 'default', ['input' => ['_drupal_ajax' => TRUE]]);
    $edit_form_rendered = \Drupal::service('renderer')->renderRoot($edit_form_render_array);

    // Test opening the edit form for the comment.
    $selector = AjaxCommentsController::getCommentSelectorPrefix() . $comment->id();
    $comment_hide_expected_response = [
      'command' => 'invoke',
      'selector' => $selector,
      'method' => 'hide',
      'args' => [],
    ];
    $edit_form_expected_response = [
      'command' => 'insert',
      'method' => 'after',
      'selector' => $selector,
      'data' => $edit_form_rendered,
      'settings' => NULL,
    ];
    $comment_hide_response_found = FALSE;
    $edit_form_response_found = FALSE;
    $edit_form_index = NULL;
    $ajax_result = $this->drupalGetAjax('ajax_comments/' . $comment->id() . '/edit');
    foreach ($ajax_result as $index => $command) {
      if ($command === $comment_hide_expected_response) {
        $comment_hide_response_found = TRUE;
      }
      elseif ($command['command'] === 'insert' && $command['method'] === 'after' && $command['selector'] === $selector) {
        $edit_form_index = $index;
        // Strip tags from the rendered content because the form build ID and
        // token will never match.
        $edit_form_expected_response['data'] = trim(Xss::filter(
          $edit_form_expected_response['data'],
          []
        ));
        $command['data'] = trim(Xss::filter(
          $command['data'],
          []
        ));
        if ($edit_form_expected_response === $command) {
          $edit_form_response_found = TRUE;
        }
      }
    }
    $this->assertTrue($comment_hide_response_found, 'The edited comment was hidden.');
    $this->assertTrue($edit_form_response_found, 'The edit form loaded.');
    $this->pass('Edit form: ' . $ajax_result[$edit_form_index]['data']);

    // Test actually editing the comment.
    $revised_comment_body = $this->randomMachineName();
    $ajax_result = $this->drupalPostAjaxForm(
    // Path.
      'comment/' . $comment->id() . '/edit',
      // Edit.
      ['comment_body[0][value]' => $revised_comment_body],
      // Triggering element.
      ['op' => t('Save')],
      // Ajax path.
      'ajax_comments/' . $comment->id() . '/update',
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      [
        'url' => Url::fromRoute(
          'ajax_comments.update',
          [
            'comment' => $comment->id(),
          ]
        )->toString(),
        'wrapper' => AjaxCommentsController::getWrapperSelector(),
        'method' => 'replace',
        'effect' => 'fade',
      ]
    );
    foreach ($ajax_result as $index => $command) {
      if ($command['command'] === 'insert' && $command['method'] === 'replaceWith') {
        $this->setRawContent($command['data']);
        $this->pass('Ajax replacement content: ' . $command['data']);
      }
    }
    $this->assertText($revised_comment_body, 'Comment edited.');
    $this->pass('Edited comment text: ' . $revised_comment_body);
  }

}