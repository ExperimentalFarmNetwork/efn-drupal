<?php

namespace Drupal\ajax_comments\Form;

use Drupal\ajax_comments\Controller\AjaxCommentsController;
use Drupal\comment\CommentInterface;
use Drupal\comment\Form\DeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides ajax enhancements to core Comment delete form.
 *
 * @package Drupal\ajax_comments
 */
class AjaxCommentsDeleteForm extends DeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, CommentInterface $comment = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Check if this form is being loaded through ajax or as a modal dialog.
    $input = $form_state->getUserInput();
    $is_ajax_request = !empty($input['_drupal_ajax']);
    $is_modal_request = \Drupal::request()->query->get('_wrapper_format') === 'drupal_modal';
    if ($is_modal_request || $is_ajax_request) {
      // In some circumstances the $comment object needs to be initialized.
      if (empty($comment)) {
        $comment = $form_state->getFormObject()->getEntity();
      }
      // Workaround for the core issue with markup in dialog titles:
      // https://www.drupal.org/node/2207247
      // Replace the emphasis tags with quote marks.
      $title_args = $form['#title']->getArguments();
      $arg_replacements = [];
      foreach ($title_args as $placeholder => $replacement) {
        if (strpos($placeholder, '%') === 0) {
          $new_placeholder = '@' . substr($placeholder, 1);
          $arg_replacements[$placeholder] = $new_placeholder;
          $title_args[$new_placeholder] = $replacement;
          unset($title_args[$placeholder]);
        }
        else {
          $arg_replacements[$placeholder] = $placeholder;
        }
      }
      $raw_string = $form['#title']->getUntranslatedString();
      $new_string = strtr($raw_string, $arg_replacements);
      $form['#title'] = $this->t($new_string, $title_args);

      // Add a class to target this form in JavaScript.
      $form['#attributes']['class'][] = 'ajax-comments';

      // Add a class to the cancel button to trigger modal dialog close.
      $form['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';

      // Set up this form to ajax submit so that we aren't redirected to
      // another page upon clicking the 'Delete' button.
      $form['actions']['submit']['#ajax'] = [
        'url' => Url::fromRoute(
          'ajax_comments.delete',
          [
            'comment' => $comment->id(),
          ]
        ),
        'wrapper' => AjaxCommentsController::getWrapperSelector(),
        'method' => 'replace',
        'effect' => 'fade',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->disableRedirect();
  }

}
