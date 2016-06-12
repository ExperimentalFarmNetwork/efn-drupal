<?php

/**
 * @file
 * Contains \Drupal\auto_nodetitle\AutoNodeTitle.
 */

namespace Drupal\auto_nodetitle;

/**
 * Provides the title generation functionality.
 */
class AutoNodeTitle {

  const AUTO_NODETITLE_DISABLED = 0;
  const AUTO_NODETITLE_ENABLED = 1;
  const AUTO_NODETITLE_OPTIONAL = 2;

  /**
   * Sets the automatically generated nodetitle for the node
   */
  public static function auto_nodetitle_set_title(&$node) {
    $types = \Drupal\node\Entity\NodeType::loadMultiple();
    $type = $node->getType();
    $title = $node->getTitle();
    $pattern = \Drupal::config('auto_nodetitle.node.' . $type)
      ->get('pattern') ?: '';
    if (trim($pattern)) {
      $node->changed = REQUEST_TIME;
      $title = self::_auto_nodetitle_patternprocessor($pattern, $node);
    }
    elseif ($node->nid) {
      $title = t('@type @node-id', array(
        '@type' => $types[$type]->get('name'),
        '@node-id' => $node->nid
      ));
    }
    else {
      $title = t('@type', array('@type' => $types[$type]->get('name')));
    }
    // Ensure the generated title isn't too long.
    $title = substr($title, 0, 255);
    $node->set('title', $title);
    // With that flag we ensure we don't apply the title two times to the same
    // node. See auto_nodetitle_is_needed().
    $node->auto_nodetitle_applied = TRUE;

    return $title;
  }

  /**
   * Returns whether the auto nodetitle has to be set.
   */
  public static function auto_nodetitle_is_needed($node) {
    $not_applied = empty($node->auto_nodetitle_applied);
    $setting = self::auto_nodetitle_get_setting($node->getType());
    $title = $node->getTitle();
    $check_optional = $setting && !($setting == self::AUTO_NODETITLE_OPTIONAL && !empty($title));
    return $not_applied && $check_optional;
  }

  /**
   * Helper function to generate the title according to the settings.
   *
   * @return a title string
   */
  protected function _auto_nodetitle_patternprocessor($pattern, $node) {
    // Replace tokens.
    $token = \Drupal::token();
    $output = $token->replace($pattern, array('node' => $node), array(
      'sanitize' => FALSE,
      'clear' => TRUE
    ));
    // Evalute PHP.
    if (\Drupal::config('auto_nodetitle.node.' . $node->getType())->get('php')) {
      $output = self::auto_nodetitle_eval($output, $node);
    }
    // Strip tags.
    $output = preg_replace('/[\t\n\r\0\x0B]/', '', strip_tags($output));
    return $output;
  }

  /**
   * Gets the auto node title setting associated with the given content type.
   */
  public static function auto_nodetitle_get_setting($type) {
    return \Drupal::config('auto_nodetitle.node.' . $type)
      ->get('status') ?: self::AUTO_NODETITLE_DISABLED;
  }

  /**
   * Evaluates php code and passes $node to it.
   */
  public static function auto_nodetitle_eval($code, $node) {
    ob_start();
    print eval('?>' . $code);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

}
