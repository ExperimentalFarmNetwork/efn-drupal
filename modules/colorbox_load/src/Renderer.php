<?php

/**
 * @file
 * Contains \Drupal\colorbox_load\Renderer.
 */

namespace Drupal\colorbox_load;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Render content in a colorbox.
 */
class Renderer implements MainContentRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();
    $content = drupal_render_root($main_content);
    $response->setAttachments($main_content['#attached']);
    $response->addCommand(new OpenCommand($content));
    return $response;
  }

}
