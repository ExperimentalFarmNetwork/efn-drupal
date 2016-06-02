<?php

namespace Drupal\geophp;

class GeoPHPWrapper {
  function __construct() {
    require_once(drupal_get_path('module', 'geophp') . '/geoPHP/geoPHP.inc');
  }
}