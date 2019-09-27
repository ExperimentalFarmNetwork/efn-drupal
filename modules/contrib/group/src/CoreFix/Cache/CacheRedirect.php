<?php

namespace Drupal\group\CoreFix\Cache;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;

/**
 * Defines a value object to represent a cache redirect with.
 *
 * @see \Drupal\group\CoreFix\Cache\VariationCache::get()
 * @see \Drupal\group\CoreFix\Cache\VariationCache::set()
 *
 * @internal
 */
class CacheRedirect implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * Constructs a CacheRedirect object.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability to redirect to.
   *
   * @see \Drupal\group\CoreFix\Cache\VariationCache::createRedirectedCacheId()
   */
  public function __construct(CacheableDependencyInterface $cacheability) {
    // Cache redirects only care about cache contexts.
    $this->cacheContexts = $cacheability->getCacheContexts();
  }

}
