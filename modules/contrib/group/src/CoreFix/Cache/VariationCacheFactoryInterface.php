<?php

namespace Drupal\group\CoreFix\Cache;

/**
 * DO NOT USE! Placeholder for when core commits this properly.
 *
 * @internal
 */
interface VariationCacheFactoryInterface {

  /**
   * Gets a variation cache backend for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a variation cache backend should be returned.
   *
   * @return \Drupal\group\CoreFix\Cache\VariationCacheInterface
   *   The variation cache backend associated with the specified bin.
   */
  public function get($bin);

}
