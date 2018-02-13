<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control field widget visibility in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "visible",
 *   label = @Translation("Visible"),
 *   description = @Translation("Dynamically control field widget visibility dependent on other field states/values. This will show the field only if the condition(s) are met (if not met the field will be hidden).")
 * )
 */
class Visible extends FieldStateBase {

}
