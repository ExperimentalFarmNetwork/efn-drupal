<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Controls details field widget expanded state in relation to other fields.
 *
 * @FieldState(
 *   id = "expanded",
 *   label = @Translation("Expanded"),
 *   description = @Translation("Dynamically expand (open) details field elements dependent on other field states/values.")
 * )
 */
class Expanded extends FieldStateBase {

}
