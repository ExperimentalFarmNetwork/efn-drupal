<?php

/**
 * @file
 * Contains \Drupal\leaflet_demo\Plugin\Block\LeafletDemoBlock.
 */

namespace Drupal\leaflet_demo\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block showcasing all Leaflet maps installed on your system.
 *
 * @Plugin(
 *   id = "leaflet_demo_block",
 *   admin_label = @Translation("Leaflet-powered maps showcase"),
 *   module = "leaflet_demo"
 * )
 */
class LeafletDemoBlock extends BlockBase {

  public function blockAccess() {
    return user_access('access content');
  }

  /* settings(), blockForm(), blockValidate() and blockSubmit() define block
   * configuration parameters on the admin/structure/block/manage page.
   * We don't have anything for that page. We only render the block.
   */

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array(
      'parameter-form' => drupal_get_form('leaflet_demo_map_parameters_form')
    );
  }
}
