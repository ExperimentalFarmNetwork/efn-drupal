<?php

namespace Drupal\field_states_ui\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a field state annotation object.
 *
 * Plugin Namespace: Plugin\FieldState.
 *
 * For a working example, see
 * \Drupal\field_states_ui\Plugin\FieldState\Visible
 *
 * @see \Drupal\field_states_ui\FieldStateInterface
 * @see \Drupal\field_states_ui\FieldStateBase
 * @see \Drupal\field_states_ui\FieldStateManager
 * @see plugin_api
 *
 * @Annotation
 */
class FieldState extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the field state.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the field state.
   *
   * This will be shown when adding or configuring this field state.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
