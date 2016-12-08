<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control field widget visibility in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "invisible",
 *   label = @Translation("Invisible (Hide)"),
 *   description = @Translation("Dynamically control field widget visibility dependent on other field states/values. This will hide the field if the condition(s) are met.")
 * )
 */
class Invisible extends FieldStateBase {

}
