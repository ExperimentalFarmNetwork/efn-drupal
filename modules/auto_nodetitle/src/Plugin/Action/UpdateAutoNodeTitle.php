<?php

/**
 * @file
 * Contains \Drupal\auto_nodetitle\Plugin\Action\UpdateAutoNodeTitle.
 */

namespace Drupal\auto_nodetitle\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

use Drupal\auto_nodetitle\AutoNodeTitle;

/**
 * Provides an action that updates nodes with their automatic titles.
 *
 * @Action(
 *   id = "auto_nodetitle_update_action",
 *   label = @Translation("Update automatic nodetitles"),
 *   type = "node"
 * )
 */
class UpdateAutoNodeTitle extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    if ($entity && AutoNodeTitle::auto_nodetitle_is_needed($entity)) {
      $previous_title = $entity->getTitle();
      AutoNodeTitle::auto_nodetitle_set_title($entity);
      // Only save if the title has actually changed.
      if ($entity->getTitle() != $previous_title) {
        $entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
