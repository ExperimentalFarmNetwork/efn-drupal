<?php

namespace Drupal\address\Plugin\views\field;

/**
 * Allows the country name to be displayed instead of the country code.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("country_code")
 *
 * @deprecated in 1.5, to be removed before 2.x. Use the Country plugin instead.
 */
class CountryCode extends Country {}
