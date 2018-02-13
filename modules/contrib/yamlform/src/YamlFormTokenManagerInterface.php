<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for token manager classes.
 */
interface YamlFormTokenManagerInterface {

  /**
   * Replace tokens in text.
   *
   * @param string|array $text
   *   A string of text that may contain tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A YAML Form or YAML Form submission entity.
   * @param array $data
   *   (optional) An array of keyed objects.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = []);

  /**
   * Build token tree link if token.module is installed.
   */
  public function buildTreeLink();

}
