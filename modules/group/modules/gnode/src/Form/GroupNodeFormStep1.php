<?php

namespace Drupal\gnode\Form;

use Drupal\node\NodeForm;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides a creating a node without it being saved yet.
 */
class GroupNodeFormStep1 extends NodeForm {

  /**
   * The private temporary store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a GroupNodeFormStep1 object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temporary store factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager, $temp_store_factory);
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue to final step'),
      '#submit' => ['::submitForm', '::saveTemporary'],
    ];

    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $actions;
  }

  /**
   * Saves a temporary node and continues to step 2 of group node creation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\gnode\Controller\GroupNodeWizardController::add()
   * @see \Drupal\gnode\Form\GroupNodeFormStep2
   */
  public function saveTemporary(array &$form, FormStateInterface $form_state) {
    $storage_id = $form_state->get('storage_id');

    $store = $this->tempStoreFactory->get('gnode_add_temp');
    $store->set("$storage_id:node", $this->entity);
    $store->set("$storage_id:step", 2);

    // Disable any URL-based redirect until the final step.
    $request = $this->getRequest();
    $form_state->setRedirectUrl(Url::fromRoute('<current>', [], ['query' => $request->query->all()]));
    $request->query->remove('destination');
  }

  /**
   * Cancels the node creation by emptying the temp store.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\gnode\Controller\GroupNodeWizardController::add()
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $form_state->get('group');

    $storage_id = $form_state->get('storage_id');
    $store = $this->tempStoreFactory->get('gnode_add_temp');
    $store->delete("$storage_id:node");

    // Redirect to the group page if no destination was set in the URL.
    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
