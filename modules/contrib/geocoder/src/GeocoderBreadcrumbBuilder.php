<?php

namespace Drupal\geocoder;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Provides a breadcrumb builder for geocoder routes.
 */
class GeocoderBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteName();
    return $route === 'geocoder.geocoder_provider.admin_add';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $parent_breadcrumb = parent::build($route_match);
    $links = $parent_breadcrumb->getLinks();
    // Remove the last breadcrumb link "Add a Geocoder provider" (related to
    // the route "geocoder.geocoder_provider.admin_add") that would produce an
    // uncaught PluginNotFoundException on its redirect.
    unset($links[6]);
    // A new Breadcrumb object is needed, because at the moment the setLinks
    // method would produce a \LogicException on the un-empty links property of
    // the existing parent_breadcrumb object.
    // @see Drupal\Core\Breadcrumb\Breadcrumb;
    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }

}
