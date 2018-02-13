<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control field widget interactability in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "disabled",
 *   label = @Translation("Disable"),
 *   description = @Translation("Dynamically control field widget interaction dependent on other field states/values. This will disable the field if the condition(s) are met.")
 * )
 */
class Disabled extends FieldStateBase {

}
