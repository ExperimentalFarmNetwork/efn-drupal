<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control checkbox field widget check state in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "checked",
 *   label = @Translation("Checked"),
 *   description = @Translation("Dynamically check checkbox dependent on other field states/values.")
 * )
 */
class Checked extends FieldStateBase {

}
