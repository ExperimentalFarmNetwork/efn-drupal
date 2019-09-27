<?php

namespace Drupal\group\CoreFix\Cache;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * DO NOT USE! Placeholder for when core commits this properly.
 *
 * @internal
 */
interface VariationCacheInterface {

  /**
   * Gets a cache entry based on cache keys.
   *
   * @param string[] $keys
   *   The cache keys to retrieve the cache entry for.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  public function get(array $keys);

  /**
   * Stores data in the cache.
   *
   * @param string[] $keys
   *   The cache keys of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cache metadata of the data to store.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   */
  public function set(array $keys, $data, CacheableDependencyInterface $cacheability);

  /**
   * Deletes an item from the cache.
   *
   * To stay consistent with ::get(), this only affects the active variation,
   * not all possible variations for the associated cache contexts.
   *
   * @param string[] $keys
   *   The cache keys of the data to delete.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   */
  public function delete(array $keys);

  /**
   * Marks a cache item as invalid.
   *
   * To stay consistent with ::get(), this only affects the active variation,
   * not all possible variations for the associated cache contexts.
   *
   * @param string[] $keys
   *   The cache keys of the data to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   */
  public function invalidate(array $keys);

}
