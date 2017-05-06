<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\file\Element\ManagedFile;

if (class_exists('\Drupal\file\Element\ManagedFile')) {

  /**
   * Provides a base class for 'managed_file' elements.
   */
  abstract class YamlFormManagedFileBase extends ManagedFile {

    /**
     * The the types of files that the server accepts.
     *
     * @var string
     *
     * @see http://www.w3schools.com/tags/att_input_accept.asp
     */
    protected static $accept;

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
      $info = parent::getInfo();
      $info['#pre_render'][] = [get_class($this), 'preRenderYamlFormManagedFile'];
      return $info;
    }

    /**
     * Render API callback: Adds media capture to the managed_file element type.
     */
    public static function preRenderYamlFormManagedFile($element) {
      if (isset($element['upload']) && static::$accept) {
        $element['upload']['#attributes']['accept'] = static::$accept;;
        $element['upload']['#attributes']['capture'] = TRUE;
      }
      return $element;
    }

  }

}
else {

  /**
   * Provides a empty base class for 'managed_file' elements.
   */
  abstract class YamlFormManagedFileBase extends FormElement {

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
      return [];
    }

  }

}
