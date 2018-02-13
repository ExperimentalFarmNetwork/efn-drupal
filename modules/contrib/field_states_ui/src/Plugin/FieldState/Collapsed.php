<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Control details field widget check state in relation to other fields dynamically.
 *
 * @FieldState(
 *   id = "collapsed",
 *   label = @Translation("Collapsed"),
 *   description = @Translation("Dynamically collapse details field elements dependent on other field states/values.")
 * )
 */
class Collapsed extends FieldStateBase {

}
