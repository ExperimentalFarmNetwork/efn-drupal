<?php

/**
 * @file
 * Contains \Drupal\ng_lightbox\NgLightboxPass
 */

namespace Drupal\ng_lightbox;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The NgLightboxPass class.
 */
class NgLightboxPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $lightbox_renderers = [];
    foreach ($container->findTaggedServiceIds('render.main_content_renderer') as $id => $attributes_list) {
      foreach ($attributes_list as $attributes) {
        if (!empty($attributes['ng_lightbox'])) {
          $format = $attributes['format'];
          $lightbox_renderers[$format] = $attributes['ng_lightbox'];
        }
      }
    }
    $container->setParameter('ng_lightbox_renderers', $lightbox_renderers);
  }

}
