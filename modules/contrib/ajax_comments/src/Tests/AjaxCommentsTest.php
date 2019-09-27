<?php

namespace Drupal\ajax_comments\Tests;

use Drupal\ajax_comments\Controller\AjaxCommentsController;
use Drupal\ajax_comments\Utility;
use Drupal\comment\Tests\CommentTestBase;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
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
   * Tests comment posting, editing, deleting, and replying using ajax.
   */
  public function testAjaxComments() {
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
    $this->node = Node::load($this->node->id());

    $comment_cid = $this->node->get('comment')->get(0)->cid;
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = Comment::load($comment_cid);

    $delete_form_render_array = $this->container
      ->get('entity.form_builder')
      ->getForm($comment, 'delete', ['input' => ['_drupal_ajax' => TRUE]]);
    $delete_form_rendered = $this->container
      ->get('renderer')
      ->renderRoot($delete_form_render_array);

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
    $wrapper_html_id = Utility::getWrapperIdFromEntity($this->node, $comment->getFieldName());

    // Test opening a modal dialog with the comment delete confirmation form.
    $modal_form_response = $this->drupalGetAjax(
      'comment/' . $comment->id() . '/delete',
      [
        'query' => [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal',
          'wrapper_html_id' => $wrapper_html_id,
        ],
      ]
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
    $ajax_settings['wrapper'] = $wrapper_html_id;

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
    $this->node = Node::load($this->node->id());
    $comment_cid = $this->node->get('comment')->get(0)->cid;
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = Comment::load($comment_cid);
    // Get the wrapper id for the comment to edit.
    $wrapper_html_id = Utility::getWrapperIdFromEntity($this->node, $comment->getFieldName());

    $edit_form_render_array = $this->container
      ->get('entity.form_builder')
      ->getForm($comment, 'default', ['input' => ['_drupal_ajax' => TRUE]]);
    $edit_form_rendered = $this->container
      ->get('renderer')
      ->renderRoot($edit_form_render_array);

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

    $ajax_result = $this->drupalGetAjax(
      'ajax_comments/' . $comment->id() . '/edit',
      [
        'query' => [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
          'wrapper_html_id' => $wrapper_html_id,
        ],
      ]
    );
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

    // Test the cancel button.
    $edit_form_dom_document = Html::load($ajax_result[$edit_form_index]['data']);
    $edit_form_id = $edit_form_dom_document->getElementsByTagName('form')->item(0)->getAttribute('id');

    $cancel_remove_form_expected_response = [
      'command' => 'remove',
      'selector' => '#' . $edit_form_id,
    ];
    $cancel_remove_messages_expected_response = [
      'command' => 'remove',
      'selector' => '#' . $wrapper_html_id . ' .js-ajax-comments-messages',
    ];
    $cancel_remove_form_response_found = FALSE;
    $cancel_remove_messages_response_found = FALSE;

    $ajax_result = $this->drupalPostAjaxForm(
      // Path.
      'comment/' . $comment->id() . '/edit',
      // Edit.
      [
        'comment_body[0][value]'  => '',
      ],
      // Triggering element.
      ['op' => t('Cancel')],
      // Ajax path.
      'ajax_comments/' . $comment->id() . '/cancel',
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      [
        'url' => Url::fromRoute(
          'ajax_comments.cancel',
          [
            'cid' => $comment->id(),
          ]
        )->toString(),
        'wrapper' => $wrapper_html_id,
        'method' => 'replace',
        'effect' => 'fade',
        'submit' => [
          'form_html_id' => $edit_form_id,
          'wrapper_html_id' => $wrapper_html_id,
        ],
      ]
    );
    foreach ($ajax_result as $index => $command) {
      if ($command == $cancel_remove_form_expected_response) {
        $cancel_remove_form_response_found = TRUE;
      }
      elseif ($command == $cancel_remove_messages_expected_response) {
        $cancel_remove_messages_response_found = TRUE;
      }
    }
    $this->assert($cancel_remove_form_response_found, 'Reply form removed.');
    $this->assert($cancel_remove_messages_response_found, 'Messages removed.');

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
      'ajax_comments/' . $comment->id() . '/save',
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      [
        'url' => Url::fromRoute(
          'ajax_comments.save',
          [
            'comment' => $comment->id(),
          ]
        )->toString(),
        'wrapper' => $wrapper_html_id,
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

    // Test replying to comments.
    // adminUser has the permission 'skip comment approval'.
    $this->drupalLogin($this->adminUser);

    // Initialize variables.
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache();
    $this->node = Node::load($this->node->id());
    $nid = $this->node->id();
    $entity_type = $this->node->getEntityTypeId();
    $pid = $this->node->get('comment')->get(0)->cid;
    /** @var \Drupal\comment\CommentInterface $comment */
    $parent_comment = Comment::load($pid);
    $comment_field_name = $parent_comment->getFieldName();
    // Get the wrapper id for the comment to edit.
    $wrapper_html_id = Utility::getWrapperIdFromEntity($this->node, $comment_field_name);

    // Return to the node page.
    $this->drupalGet('node/' . $nid);

    // Test opening the comment reply form.
    $selector = AjaxCommentsController::getCommentSelectorPrefix() . $pid;
    $reply_comment = $this->container->get('entity_type.manager')
      ->getStorage('comment')
      ->create([
      'entity_id' => $nid,
      'pid' => $pid,
      'entity_type' => $entity_type,
      'field_name' => $comment_field_name,
      ]);
    $reply_form_render_array = $this->container
      ->get('entity.form_builder')
      ->getForm(
        $reply_comment,
        'default',
        ['input' => [AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER => TRUE]]
      );
    $reply_form_rendered = $this->container
      ->get('renderer')
      ->renderRoot($reply_form_render_array);

    $reply_form_expected_response = [
      'command' => 'insert',
      'method' => 'after',
      'selector' => $selector,
      'data' => $reply_form_rendered,
      'settings' => NULL,
    ];

    $reply_form_response_found = FALSE;
    $reply_form_index = NULL;

    $ajax_result = $this->drupalGetAjax(
      'ajax_comments/reply/' . $entity_type . '/' . $nid . '/' . $comment_field_name . '/' . $pid,
      [
        'query' => [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
          'wrapper_html_id' => $wrapper_html_id,
        ],
      ]
    );
    foreach ($ajax_result as $index => $command) {
      if ($command['command'] === 'insert' && $command['method'] === 'after' && $command['selector'] === $selector) {
        $reply_form_index = $index;
        // Strip tags from the rendered content because the form build ID and
        // token will never match.
        $reply_form_expected_response['data'] = trim(Xss::filter(
          $reply_form_expected_response['data'],
          []
        ));
        $command['data'] = trim(Xss::filter(
          $command['data'],
          []
        ));
        if ($reply_form_expected_response === $command) {
          $reply_form_response_found = TRUE;
        }
      }
    }
    $this->assertTrue($reply_form_response_found, 'The reply form loaded.');
    $this->pass('Reply form: ' . $ajax_result[$reply_form_index]['data']);

    // Test the cancel button.
    $reply_form_dom_document = Html::load($ajax_result[$reply_form_index]['data']);
    $reply_form_id = $reply_form_dom_document->getElementsByTagName('form')->item(0)->getAttribute('id');

    $cancel_remove_form_expected_response = [
      'command' => 'remove',
      'selector' => '#' . $reply_form_id,
    ];
    $cancel_remove_messages_expected_response = [
      'command' => 'remove',
      'selector' => '#' . $wrapper_html_id . ' .js-ajax-comments-messages',
    ];
    $cancel_remove_form_response_found = FALSE;
    $cancel_remove_messages_response_found = FALSE;

    $path = 'comment/reply/' .
      $entity_type .
      '/' . $nid .
      '/' . $comment_field_name .
      '/' . $pid;
    $ajax_result = $this->drupalPostAjaxForm(
      // Path.
      $path,
      // Edit.
      [
        'comment_body[0][value]'  => '',
      ],
      // Triggering element.
      ['op' => t('Cancel')],
      // Ajax path.
      'ajax_comments/0/cancel',
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      [
        'url' => Url::fromRoute(
          'ajax_comments.cancel',
          [
            'cid' => 0,
          ]
        )->toString(),
        'wrapper' => $wrapper_html_id,
        'method' => 'replace',
        'effect' => 'fade',
        'submit' => [
          'form_html_id' => $reply_form_id,
          'wrapper_html_id' => $wrapper_html_id,
        ],
      ]
    );
    foreach ($ajax_result as $index => $command) {
      if ($command == $cancel_remove_form_expected_response) {
        $cancel_remove_form_response_found = TRUE;
      }
      elseif ($command == $cancel_remove_messages_expected_response) {
        $cancel_remove_messages_response_found = TRUE;
      }
    }
    $this->assert($cancel_remove_form_response_found, 'Reply form removed.');
    $this->assert($cancel_remove_messages_response_found, 'Messages removed.');

    // Test actually replying to the comment.
    $comment_text_3 = $this->randomMachineName();
    $path = 'comment/reply/' .
      $entity_type .
      '/' . $nid .
      '/' . $comment_field_name .
      '/' . $pid;
    $ajax_path = 'ajax_comments/save_reply/' .
      $entity_type .
      '/' . $nid .
      '/' . $comment_field_name .
      '/' . $pid;
    $ajax_result = $this->drupalPostAjaxForm(
      // Path.
      $path,
      // Edit.
      ['comment_body[0][value]' => $comment_text_3],
      // Triggering element.
      ['op' => t('Save')],
      // Ajax path.
      $ajax_path,
      // Options.
      [],
      // Headers.
      [],
      // HTML ID.
      NULL,
      // Ajax settings.
      [
        'url' => Url::fromRoute(
          'ajax_comments.save_reply',
          [
            'entity_type' => $entity_type,
            'entity' => $nid,
            'field_name' => $comment_field_name,
            'pid' => $pid,
          ]
        )->toString(),
        'wrapper' => $wrapper_html_id,
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
    $this->assertText($comment_text_3, 'Comment reply posted.');
    $this->pass('Reply comment text: ' . $comment_text_3);
  }

}
