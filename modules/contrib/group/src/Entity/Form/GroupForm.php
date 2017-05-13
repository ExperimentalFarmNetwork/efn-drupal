<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group edit forms.
 *
 * @ingroup group
 */
class GroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // We call the parent function first so the entity is saved. We can then
    // read out its ID and redirect to the canonical route.
    $return = parent::save($form, $form_state);

    // Display success message.
    $t_args = [
      '@type' => $this->entity->getGroupType()->label(),
      '%title' => $this->entity->label(),
    ];

    drupal_set_message($this->operation == 'edit'
      ? $this->t('@type %title has been updated.', $t_args)
      : $this->t('@type %title has been created.', $t_args)
    );

    $form_state->setRedirect('entity.group.canonical', ['group' => $this->entity->id()]);
    return $return;
  }

}
