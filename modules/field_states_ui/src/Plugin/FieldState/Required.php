<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control field widget interactability in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "required",
 *   label = @Translation("Required"),
 *   description = @Translation("Dynamically make field required dependent on other field states/values.")
 * )
 */
class Required extends FieldStateBase {

}
