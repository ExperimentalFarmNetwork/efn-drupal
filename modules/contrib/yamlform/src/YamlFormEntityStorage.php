<?php

namespace Drupal\yamlform;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "yamlform" configuration entities.
 */
class YamlFormEntityStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   *
   * Config entities are not cached and there is no easy way to enable static
   * caching. See: Issue #1885830: Enable static caching for config entities.
   *
   * Overriding just EntityStorageBase::load is much simpler
   * than completely re-writting EntityStorageBase::loadMultiple. It is also
   * worth noting that EntityStorageBase::resetCache() does purge all cached
   * yamlform config entities.
   *
   * Forms need to be cached when they are being loading via
   * a form submission, which requires a form's elements and meta data to be
   * initialized via YamlForm::initElements().
   *
   * @see https://www.drupal.org/node/1885830
   * @see \Drupal\Core\Entity\EntityStorageBase::resetCache()
   * @see \Drupal\yamlform\Entity\YamlForm::initElements()
   */
  public function load($id) {
    if (isset($this->entities[$id])) {
      return $this->entities[$id];
    }

    $this->entities[$id] = parent::load($id);
    return $this->entities[$id];
  }

}
