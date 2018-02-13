<?php

namespace Drupal\yamlform;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for help classes.
 */
interface YamlFormHelpManagerInterface {

  /**
   * Get help.
   *
   * @param string|null $id
   *   (optional) Help id.
   *
   * @return array|mixed
   *   A single help item or all help.
   */
  public function getHelp($id = NULL);

  /**
   * Get video.
   *
   * @param string|null $id
   *   (optional) Video id.
   *
   * @return array|mixed
   *   A single help item or all videos.
   */
  public function getVideo($id = NULL);

  /**
   * Build help for specific route.
   *
   * @param string $route_name
   *   The route for which to find help.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to find help.
   *
   * @return array
   *   An render array containing help for specific route.
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match);

  /**
   * Build the main help page for the YAML Form module.
   *
   * @return array
   *   An render array containing help for the YAML Form module.
   */
  public function buildIndex();

}
