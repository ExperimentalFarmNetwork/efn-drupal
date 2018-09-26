<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Controls field widget optional state in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "optional",
 *   label = @Translation("Optional"),
 *   description = @Translation("Dynamically make field optional dependent on other field states/values.")
 * )
 */
class Optional extends FieldStateBase {

}
