<?php

namespace Drupal\ajax_comments\Form;

use Drupal\ajax_comments\TempStore;
use Drupal\ajax_comments\Utility;
use Drupal\comment\CommentInterface;
use Drupal\comment\Form\DeleteForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides ajax enhancements to core Comment delete form.
 *
 * @package Drupal\ajax_comments
 */
class AjaxCommentsDeleteForm extends DeleteForm {

  /**
   * The TempStore service.
   *
   * This service stores temporary data to be used across HTTP requests.
   *
   * @var \Drupal\ajax_comments\TempStore
   */
  protected $tempStore;

  /**
   * Constructs an AjaxCommentsDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\ajax_comments\TempStore $temp_store
   *   The TempStore service.
   */
  public function __construct(EntityManagerInterface $entity_manager, TempStore $temp_store) {
    parent::__construct($entity_manager);
    $this->tempStore = $temp_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('ajax_comments.temp_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, CommentInterface $comment = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Check if this form is being loaded through ajax or as a modal dialog.
    $request = $this->requestStack->getCurrentRequest();
    $is_ajax_request = Utility::isAjaxRequest($request, $form_state->getUserInput());
    $is_modal_request = Utility::isModalRequest($request);
    if ($is_modal_request || $is_ajax_request) {
      // In some circumstances the $comment object needs to be initialized.
      if (empty($comment)) {
        $comment = $form_state->getFormObject()->getEntity();
      }

      // Get the selectors from the request.
      $this->tempStore->getSelectors($request, $overwrite = TRUE);
      $wrapper_html_id = $this->tempStore->getSelectorValue($request, 'wrapper_html_id');

      // Add the wrapping fields's HTML id as a hidden input
      // so we can access it in the controller.
      $form['wrapper_html_id'] = [
        '#type' => 'hidden',
        '#value' => $wrapper_html_id,
      ];

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
        'wrapper' => $wrapper_html_id,
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
    $request = $this->requestStack->getCurrentRequest();
    // Disable the form redirect if the delete confirmation form was loaded
    // through ajax in a modal dialog box, but allow redirect if the user
    // manually opens the link in a new window or tab (e.g., /comment/1/delete).
    if (Utility::isAjaxRequest($request)) {
      $form_state->disableRedirect();
    }
  }

}
