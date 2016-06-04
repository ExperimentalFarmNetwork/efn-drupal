<?php
namespace Drupal\auto_entitylabel;

class AutoENtityLabelManager {
  /**
   * Constructs the list of options for the given bundle.
   */
  public static function auto_entitylabel_options($entity_type, $bundle_name) {
    $options = array(
      AUTO_ENTITYLABEL_DISABLED => t('Disabled'),
    );
    if (self::auto_entitylabel_entity_label_visible($entity_type)) {
      $options += array(
        AUTO_ENTITYLABEL_ENABLED => t('Automatically generate the label and hide the label field'),
        AUTO_ENTITYLABEL_OPTIONAL => t('Automatically generate the label if the label field is left empty'),
      );
    }
    else {
      $options += array(
        AUTO_ENTITYLABEL_ENABLED => t('Automatically generate the label'),
      );
    }
    return $options;
  }

  /**
   * Check if given entity bundle has a visible label on the entity form.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle_name
   *   The name of the bundle.
   *
   * @return
   *   TRUE if the label is rendered in the entity form, FALSE otherwise.
   *
   * @todo
   *   Find a generic way of determining the result of this function. This
   *   will probably require access to more information about entity forms
   *   (entity api module?).
   */
  public static function auto_entitylabel_entity_label_visible($entity_type) {
    $hidden = array(
      'profile2' => TRUE,
    );

    return empty($hidden[$entity_type]);
  }
}