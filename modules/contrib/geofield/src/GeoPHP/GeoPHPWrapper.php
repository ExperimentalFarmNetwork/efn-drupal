<?php

namespace Drupal\geofield\GeoPHP;

class GeoPHPWrapper implements GeoPHPInterface {

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
