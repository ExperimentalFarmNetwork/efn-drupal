<?php

namespace Drupal\geocoder;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Geocoder\Model\Address;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for geocoder dumper plugins.
 */
abstract class DumperBase extends PluginBase implements DumperInterface, ContainerFactoryPluginInterface {

  /**
   * The geocoder dumper handler.
   *
   * @var \Geocoder\Dumper\Dumper
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function dump(Address $address) {
    return $this->getHandler()->dump($address);
  }

  /**
   * Returns the dumper handler.
   *
   * @return \Geocoder\Dumper\Dumper
   *   Returns dumper handler.
   */
  protected function getHandler() {
    if (!isset($this->handler)) {
      $definition = $this->getPluginDefinition();
      $class = $definition['handler'];
      $this->handler = new $class();
    }
    return $this->handler;
  }

}
