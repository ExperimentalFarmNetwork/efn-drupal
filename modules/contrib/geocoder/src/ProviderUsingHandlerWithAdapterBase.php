<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Ivory\HttpAdapter\HttpAdapterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for providers using handlers with HTTP adapter.
 */
abstract class ProviderUsingHandlerWithAdapterBase extends ProviderUsingHandlerBase {

  /**
   * The HTTP adapter.
   *
   * @var \Ivory\HttpAdapter\HttpAdapterInterface
   */
  protected $httpAdapter;

  /**
   * Constructs a geocoder provider plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend used to cache geocoding data.
   * @param \Ivory\HttpAdapter\HttpAdapterInterface $http_adapter
   *   The HTTP adapter.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, HttpAdapterInterface $http_adapter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend);
    $this->httpAdapter = $http_adapter;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cache.geocoder'),
      $container->get('geocoder.http_adapter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getArguments() {
    return array_merge([$this->httpAdapter], parent::getArguments());
  }

}
