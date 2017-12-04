<?php

namespace Drupal\efn\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path of mymodule.mypage to use a hyphen
    if ($route = $collection->get('entity.group.add_form')) {
        // set the title (or override '_title_callback' below)
        $route->setDefault('_title', 'Start a Project');
        // // unset the _title_callback; alternatively you could override it here
        $route->setDefault('_title_callback', '');
    }
  }

}
