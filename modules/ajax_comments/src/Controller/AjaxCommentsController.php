<?php

namespace Drupal\ajax_comments\Controller;

use Drupal\ajax_comments\Ajax\ajaxCommentsScrollToElementCommand;
use Drupal\ajax_comments\Form\AjaxCommentsForm;
use Drupal\comment\CommentInterface;
use Drupal\comment\Controller\CommentController;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Controller routines for AJAX comments routes.
 */
class AjaxCommentsController extends ControllerBase {

  /**
   * Class(es) to apply to the outermost wrapper element of the comments field.
   *
   * TODO: Maybe make this configurable?
   *
   * @var array
   *  An array of class names.
   */
  public static $commentWrapperClasses = [
    'js-ajax-comments-wrapper',
  ];

  /**
   * Class prefix to apply to each comment.
   *
   * @var string
   *   A prefix used to build class name applied to each comment.
   */
  public static $commentClassPrefix = 'js-ajax-comments-id-';

  /**
   * Service to turn render arrays into HTML strings.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a AjaxCommentsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * Get a selector for the wrapper class(es) of the comments field.
   *
   * @return string
   *   A selector based on the wrapper classes of the comments field.
   */
  public static function getWrapperSelector() {
    return '.' . implode('.', static::$commentWrapperClasses);
  }

  /**
   * Get the prefix for a selector class for an individual comment.
   *
   * @return string
   *   The portion of a CSS class name that prepends the comment ID.
   */
  public static function getCommentSelectorPrefix() {
    return '.' . static::$commentClassPrefix;
  }

  /**
   * Build a comment field render array for the ajax response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that has the comment field.
   * @param string $field_name
   *   The machine name of the comment field.
   *
   * @return array
   *   A render array for the updated comment field.
   */
  public function renderCommentField(EntityInterface $entity, $field_name) {
    $comment_field = $entity->get($field_name);
    // Load the display settings to ensure that the field formatter
    // configuration is properly applied to the rendered field when it is
    // returned in the ajax response.
    $display_options = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default')
      ->getComponent($field_name);
    $comment_display = $comment_field->view($display_options);

    // To avoid infinite nesting of #theme_wrappers elements on subsequent
    // ajax responses, unset them here.
    unset($comment_display['#theme_wrappers']);

    return $comment_display;
  }

  /**
   * Create an ajax response to replace the comment field.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object being built.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that has the comment field.
   * @param string $field_name
   *   The machine name of the comment field.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The modified ajax response.
   */
  public function buildCommentFieldResponse(AjaxResponse $response, EntityInterface $entity, $field_name) {
    // Build a comment field render array for the ajax response.
    $comment_display = $this->renderCommentField($entity, $field_name);

    // Rendering the comment form below (as part of comment_display) triggers
    // form processing.
    $response->addCommand(new ReplaceCommand(static::getWrapperSelector(), $comment_display));

    return $response;
  }

  /**
   * Add messages to the ajax response.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object being built.
   * @param string $selector
   *   The DOM selector used to insert status messages.
   * @param string $position
   *   Indicates whether to use PrependCommand or BeforeCommand.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The modified ajax response.
   */
  public function addMessages(AjaxResponse $response, $selector = '', $position = 'prepend') {
    $settings = \Drupal::config('ajax_comments.settings');
    $notify = $settings->get('notify');

    if ($notify) {
      if (empty($selector)) {
        // Use the wrapper for the entire comment field as the default selector
        // for inserting messages, if no selector is specified.
        $selector = static::getWrapperSelector();
      }
      // Add any status messages.
      $status_messages = ['#type' => 'status_messages'];

      switch ($position) {
        case 'before':
          $command = new BeforeCommand(
            $selector,
            $this->renderer->renderRoot($status_messages)
          );
          break;

        case 'prepend':
        default:
          $command = new PrependCommand(
            $selector,
            $this->renderer->renderRoot($status_messages)
          );
      }
      $response->addCommand(
        $command
      );
    }

    return $response;
  }

  /**
   * Returns the comment edit form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function edit(Request $request, CommentInterface $comment) {
    $is_ajax = $request->request->get('_drupal_ajax', FALSE) || $request->query->get('_wrapper_format') === 'drupal_ajax';

    if ($is_ajax) {
      $response = new AjaxResponse();

      // Hide anchor.
      $response->addCommand(new InvokeCommand('a#comment-' . $comment->id(), 'hide'));

      // Hide comment.
      $response->addCommand(new InvokeCommand(static::getCommentSelectorPrefix() . $comment->id(), 'hide'));

      // Insert the comment form.
      $form = $this->entityFormBuilder()->getForm($comment);
      $response->addCommand(new AfterCommand(static::getCommentSelectorPrefix() . $comment->id(), $form));

      // TODO: Get this custom ajax command working later.
      // if (\Drupal::config('ajax_comments.settings')->get('enable_scroll')) {
      //   $response->addCommand(new ajaxCommentsScrollToElementCommand('.ajax-comments-reply-form-' . $comment->getCommentedEntityId() . '-' . $comment->get('pid')->target_id . '-' . $comment->id()));
      // }

      return $response;
    }
    else {
      // If the user attempts to access the edit link directly (e.g., at
      // /ajax_comments/1/edit), redirect to the core comment edit form.
      $redirect = Url::fromRoute(
        'entity.comment.edit_form',
        ['comment' => $comment->id()]
      )
        ->setAbsolute()
        ->toString();
      $response = new RedirectResponse($redirect);
      return $response;
    }

  }

  /**
   * Submit handler for the comment edit form.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function update(CommentInterface $comment) {
    $response = new AjaxResponse();

    // Rebuild the form to trigger form submission.
    $this->entityFormBuilder()->getForm($comment, 'default');

    // Build the updated comment field and insert into a replaceWith
    // response. Also prepend any status messages in the response.
    $response = $this->buildCommentFieldResponse(
      $response,
      $comment->getCommentedEntity(),
      $comment->get('field_name')->value
    );
    $response = $this->addMessages(
      $response,
      static::getCommentSelectorPrefix() . $comment->id(),
      'before'
    );

    return $response;
  }

  /**
   * Cancel handler for the comment edit form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function cancel(Request $request, CommentInterface $comment) {
    $response = new AjaxResponse();

    // Show the hidden anchor.
    $response->addCommand(new InvokeCommand('a#comment-' . $comment->id(), 'show', [200, 'linear']));

    // Show the hidden comment.
    $response->addCommand(new InvokeCommand(static::getCommentSelectorPrefix() . $comment->id(), 'show', [200, 'linear']));

    // Remove the form.
    $response->addCommand(new RemoveCommand('#' . $request->request->get('html_id')));

    return $response;
  }

  /**
   * Builds ajax response for deleting a comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function delete(CommentInterface $comment) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    // Rebuild the form to trigger form submission.
    $this->entityFormBuilder()->getForm($comment, 'delete');

    // Build the updated comment field and insert into a replaceWith response.
    // Also prepend any status messages in the response.
    $response = $this->buildCommentFieldResponse(
      $response,
      $comment->getCommentedEntity(),
      $comment->get('field_name')->value
    );
    $response = $this->addMessages(
      $response,
      AjaxCommentsForm::getFormSelector()
    );

    return $response;
  }

  /**
   * Builds ajax response for adding a comment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   *
   * @see \Drupal\comment\Controller\CommentController::getReplyForm()
   */
  public function reply(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $response = new AjaxResponse();

    // Check the user's access to reply.
    // The user should not have made it this far without proper permission,
    // but adding this access check as a fallback.
    $this->replyAccess($request, $response, $entity, $field_name, $pid);

    // Build the updated comment field and insert into a replaceWith response.
    // Also prepend any status messages in the response.
    $response = $this->buildCommentFieldResponse(
      $response,
      $entity,
      $field_name
    );
    $response = $this->addMessages(
      $response,
      AjaxCommentsForm::getFormSelector()
    );

    return $response;
  }

  /**
   * Check the user's permission to post a comment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object being built.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response, if access is denied.
   */
  public function replyAccess(Request $request, AjaxResponse $response, EntityInterface $entity, $field_name, $pid = NULL) {
    $access = CommentController::create(\Drupal::getContainer())
      ->replyFormAccess($entity, $field_name, $pid);

    if ($access->isForbidden()) {
      $selector = $request->request->get('html_id');
      if ($selector) {
        $selector = '#' . $selector;
      }
      else {
        $selector = static::getWrapperSelector();
      }
      drupal_set_message(t('You do not have permission to post a comment.'), 'error');
      $status_messages = ['#type' => 'status_messages'];
      $response->addCommand(new ReplaceCommand(
        $selector,
        $this->renderer->renderRoot($status_messages)
      ));

      return $response;
    }
  }

}
