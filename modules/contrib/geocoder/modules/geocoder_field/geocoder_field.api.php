<?php

/**
 * @file
 * Hooks provided by the Geocoder Field module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the List of Field types objects of Geocoding operations.
 *
 * Modules may implement this hook to alter the list of possible Geocoding
 * Field Types.
 *
 * @param array $source_fields_types
 *   The list of possible Geocoding Field Types.
 *
 * @see \Drupal\search_api\Backend\BackendPluginBase
 */
function hook_geocode_source_fields_alter(array &$source_fields_types) {
  array_push($source_fields_types,
    "my_new_field_1",
    "my_new_field_2"
  );
}

/**
 * Alter the List of Field types objects of Reverse Geocoding operations.
 *
 * Modules may implement this hook to alter the list of Reverse Geocoding
 * Field Types.
 *
 * @param array $source_fields_types
 *   The list of possible Reverse Geocoding Field Types.
 *
 * @see \Drupal\search_api\Backend\BackendPluginBase
 */
function hook_reverse_geocode_source_fields_alter(array &$source_fields_types) {
  array_push($source_fields_types,
    "my_new_field_1",
    "my_new_field_2"
  );
}

/**
 * @} End of "addtogroup hooks".
 */
