<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control checkbox field widget check state in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "unchecked",
 *   label = @Translation("Unchecked"),
 *   description = @Translation("Dynamically uncheck checkbox dependent on other field states/values.")
 * )
 */
class Unchecked extends FieldStateBase {

}
