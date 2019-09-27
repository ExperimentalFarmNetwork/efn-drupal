<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\CoreFix\Cache\VariationCacheInterface;

/**
 * Collects group permissions for an account.
 */
class ChainGroupPermissionCalculator implements ChainGroupPermissionCalculatorInterface {

  /**
   * The calculators.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface[]
   */
  protected $calculators = [];

  /**
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\group\CoreFix\Cache\VariationCacheInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\group\CoreFix\Cache\VariationCacheInterface
   */
  protected $static;

  /**
   * Constructs a ChainGroupPermissionCalculator object.
   *
   * @param \Drupal\group\CoreFix\Cache\VariationCacheInterface $cache
   *   The variation cache to use for the persistent cache.
   * @param \Drupal\group\CoreFix\Cache\VariationCacheInterface $static
   *   The variation cache to use for the static cache.
   */
  public function __construct(VariationCacheInterface $cache, VariationCacheInterface $static) {
    $this->cache = $cache;
    $this->static = $static;
  }

  /**
   * {@inheritdoc}
   */
  public function addCalculator(GroupPermissionCalculatorInterface $calculator) {
    $this->calculators[] = $calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculators() {
    return $this->calculators;
  }

  /**
   * Performs the calculation of permissions with caching support.
   *
   * @param string[] $cache_keys
   *   The cache keys to store the calculation with.
   * @param string $method
   *   The method to invoke on each calculator.
   * @param array $args
   *   The arguments to pass to the calculator method.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   The calculated group permissions, potentially served from a cache.
   */
  protected function doCacheableCalculation($cache_keys, $method, array $args = []) {
    // Retrieve the permissions from the static cache if available.
    if ($static_cache = $this->static->get($cache_keys)) {
      return $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cache_keys)) {
      $calculated_permissions = $cache->data;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $calculated_permissions = new RefinableCalculatedGroupPermissions();
      foreach ($this->getCalculators() as $calculator) {
        $calculated_permissions = $calculated_permissions->merge(call_user_func_array([$calculator, $method], $args));
      }

      // Cache the permissions as an immutable value object.
      $calculated_permissions = new CalculatedGroupPermissions($calculated_permissions);
      $this->cache->set($cache_keys, $calculated_permissions, $calculated_permissions);
    }

    // Store the permissions in the static cache.
    $this->static->set($cache_keys, $calculated_permissions, $calculated_permissions);

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAnonymousPermissions() {
    return $this->doCacheableCalculation(['group_permissions', 'anonymous'], __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateOutsiderPermissions(AccountInterface $account) {
    return $this->doCacheableCalculation(['group_permissions', 'outsider'], __FUNCTION__, [$account]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    return $this->doCacheableCalculation(['group_permissions', 'member'], __FUNCTION__, [$account]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateAuthenticatedPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->merge($this->calculateOutsiderPermissions($account))
      ->merge($this->calculateMemberPermissions($account));
    return new CalculatedGroupPermissions($calculated_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account) {
    return $account->isAnonymous()
      ? $this->calculateAnonymousPermissions()
      : $this->calculateAuthenticatedPermissions($account);
  }

}
