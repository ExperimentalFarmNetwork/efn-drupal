<?php

namespace Drupal\geocoder_field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for the Preprocessor plugin.
 */
abstract class PreprocessorBase extends PluginBase implements PreprocessorInterface, ContainerFactoryPluginInterface {

  /**
   * The field that needs to be preprocessed.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function setField(FieldItemListInterface $field) {
    $this->field = $field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValues(array &$values) {
    $values = $this->setValues($values)->getValues();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess() {
    if (!isset($this->field)) {
      throw new \RuntimeException('A field (\Drupal\Core\Field\FieldItemListInterface) must be set with ::setField() before preprocessing.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPreparedReverseGeocodeValues(array $values = []) {
    return array_map(function ($value) {
      return array_combine(['lat', 'lon'], array_map(
        'trim',
        explode(',', trim($value['value']), 2)
      ));
    }, $values);
  }

}
