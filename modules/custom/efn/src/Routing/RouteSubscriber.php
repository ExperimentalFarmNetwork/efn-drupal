<?php

namespace Drupal\efn\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group.add_form')) {
        // set the title
        $route->setDefault('_title', 'Start a Project');
        // // unset the _title_callback;
        $route->setDefault('_title_callback', '');
    }
    if ($route = $collection->get('entity.profile.type.user_profile_form')) {
        // Note: get username and put it here
        $route->setDefault('_title', 'Edit profile');
        $route->setDefault('_title_callback', '');
    }
  }
}
