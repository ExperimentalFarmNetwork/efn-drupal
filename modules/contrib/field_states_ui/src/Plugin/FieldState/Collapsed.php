<?php

namespace Drupal\field_states_ui\Plugin\FieldState;

use Drupal\field_states_ui\FieldStateBase;

/**
 * Controls details field widget collapsed state in relation to other fields.
 *
 * @FieldState(
 *   id = "collapsed",
 *   label = @Translation("Collapsed"),
 *   description = @Translation("Dynamically collapse details field elements dependent on other field states/values.")
 * )
 */
class Collapsed extends FieldStateBase {

}
