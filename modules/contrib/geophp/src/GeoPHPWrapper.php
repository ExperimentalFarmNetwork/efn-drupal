<?php

namespace Drupal\geophp;

class GeoPHPWrapper implements GeoPHPInterface {


  function __construct() {
    require_once(drupal_get_path('module', 'geophp') . '/geoPHP/geoPHP.inc');
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    return \geoPHP::version();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return call_user_func_array(array('\geoPHP', 'load'), func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterMap() {
    return call_user_func_array(array('\geoPHP', 'getAdapterMap'), func_get_args());
  }

}
