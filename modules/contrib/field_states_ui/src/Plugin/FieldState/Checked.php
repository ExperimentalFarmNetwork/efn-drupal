<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Controls checkbox field widget checked state in relation to other fields.
 *
 * @FieldState(
 *   id = "checked",
 *   label = @Translation("Checked"),
 *   description = @Translation("Dynamically check checkbox dependent on other field states/values.")
 * )
 */
class Checked extends FieldStateBase {

}
