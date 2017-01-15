<?php

/**
 * @file
 * Contains \Drupal\ng_lightbox\NgLightboxServiceProvider
 */

namespace Drupal\ng_lightbox;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * The NgLightboxServiceProvider class.
 */
class NgLightboxServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new NgLightboxPass());
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $this->addLightbox($container, 'main_content_renderer.dialog', 'Core Dialog');
    $this->addLightbox($container, 'main_content_renderer.modal', 'Core Modal');
  }

  /**
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   * @param $id
   * @param $title
   */
  protected function addLightbox(ContainerBuilder $container, $id, $title) {
    $definition = $container->getDefinition($id);
    $tags = $definition->getTags();

    foreach ($tags as $delta => &$tag) {
      if ($delta === 'render.main_content_renderer') {
        foreach ($tag as &$attribute) {
          $attribute['ng_lightbox'] = $title;
        }
      }
    }

    $definition->setTags($tags);
  }

}
