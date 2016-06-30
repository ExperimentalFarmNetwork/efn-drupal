<?php

namespace Drupal\ajax_comments\Form;

use Drupal\ajax_comments\Controller\AjaxCommentsController;
use Drupal\comment\CommentForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides ajax enhancements to core default Comment form.
 *
 * @package Drupal\ajax_comments
 */
class AjaxCommentsForm extends CommentForm {

  /**
   * Class(es) to apply to the comments form.
   *
   * TODO: Maybe make this configurable?
   *
   * @var array
   *  An array of class names.
   */
  public static $formClasses = [
    'js-ajax-comments-form',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * Constructs a new CommentForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user, RendererInterface $renderer, RequestStack $request_stack) {
    parent::__construct($entity_manager, $current_user, $renderer);
    $this->requestStack = $request_stack;
  }

  /**
   * Get a selector for the known class(es) of the comments form.
   *
   * Although the comments form may contain other CSS classes, this method
   * returns a list of classes added by the AjaxCommentsForm PHP class,
   * on which we can rely in our PHP logic.
   *
   * @return string
   *   A selector based on the known classes of the comments form.
   */
  public static function getFormSelector() {
    return '.' . implode('.', static::$formClasses);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if (empty($form['#attributes']['class'])) {
      $form['#attributes']['class'] = static::$formClasses;
    }
    else {
      $form['#attributes']['class'] = array_unique(array_merge($form['#attributes']['class'], static::$formClasses));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Populate the comment-specific variables.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\Core\Entity\EntityInterface $commented_entity */
    $commented_entity = $comment->getCommentedEntity();
    $field_name = $comment->getFieldName();
    $cid = $comment->id() ? $comment->id() : 0;
    $pid = $comment->get('pid')->target_id ? $comment->get('pid')->target_id : 0;

    // Build the #ajax array.
    $ajax = [
      // Due to D8 core comments' use of #lazy_builder, setting a 'callback'
      // here won't work. The Drupal 8 form ajax callback functionality
      // relies on FormBuilder::buildForm() throwing an FormAjaxException()
      // during processing. The exception would be caught in Symfony's
      // HttpKernel::handle() method, which handles the exception and gets
      // responses from event subscribers, in this case FormAjaxSubscriber.
      // However, #lazy_builder causes the comment form to be built on a
      // separate, subsequent request, which causes HttpKernel::handle()
      // to be unable to catch the FormAjaxException. Using an ajax 'url'
      // instead of a callback avoids this issue.
      // The ajax URL varies based on context, so set a placeholder and
      // override below.
      'url' => NULL,
      'wrapper' => AjaxCommentsController::getWrapperSelector(),
      'method' => 'replace',
      'effect' => 'fade',
    ];

    // The form actions will vary based on the route
    // that is requesting this form.
    $request = $this->requestStack->getCurrentRequest();
    $route_name = RouteMatch::createFromRequest($request)->getRouteName();

    switch ($route_name) {
      case 'entity.comment.edit_form':
        // If we're on the standalone comment edit page (/comment/{cid}/edit),
        // don't add the ajax behavior.
        break;

      case 'ajax_comments.edit':
        $element['submit']['#ajax'] = $ajax;
        $element['submit']['#ajax']['url'] = Url::fromRoute(
          'ajax_comments.update',
          [
            'comment' => $comment->id(),
          ]
        );
        $element['cancel'] = [
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#access' => TRUE,
          '#ajax' => [
            'url' => Url::fromRoute(
              'ajax_comments.cancel',
              [
                'comment' => $comment->id(),
              ]
            ),
            'wrapper' => AjaxCommentsController::getWrapperSelector(),
            'method' => 'replace',
            'effect' => 'fade',
          ],
        ];

        break;

      default:
        $element['submit']['#ajax'] = $ajax;
        $element['submit']['#ajax']['url'] = Url::fromRoute(
          'ajax_comments.reply',
          [
            'entity_type' => $commented_entity->getEntityTypeId(),
            'entity' => $commented_entity->id(),
            'field_name' => $field_name,
            'pid' => $pid,
          ]
        );

        break;
    }

    return $element;
  }

  /**
   * Override the redirect set by \Drupal\comment\CommentForm::save().
   *
   * Drupal needs to redirect the form back to itself so that processing
   * completes and the new comments appears in the markup returned by the
   * ajax response. If we merely unset the redirect to the node page, the new
   * comment will not appear until the next page refresh.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Code adapted from FormSubmitter::redirectForm().
    $request = $this->requestStack->getCurrentRequest();
    $form_state->setRedirect(
      '<current>',
      [],
      ['query' => $request->query->all(), 'absolute' => TRUE]
    );
  }

}
