<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Controls checkbox field widget unchecked state in relation to other fields.
 *
 * @FieldState(
 *   id = "unchecked",
 *   label = @Translation("Unchecked"),
 *   description = @Translation("Dynamically uncheck checkbox dependent on other field states/values.")
 * )
 */
class Unchecked extends FieldStateBase {

}
