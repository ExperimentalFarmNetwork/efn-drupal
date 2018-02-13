<?php

namespace Drupal\yamlform;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\yamlform\Entity\YamlFormOptions;

/**
 * Defines a class to build a listing of form options entities.
 *
 * @see \Drupal\yamlform\Entity\YamlFormOption
 */
class YamlFormOptionsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['options'] = [
      'data' => $this->t('Options'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['alter'] = [
      'data' => $this->t('Altered'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\yamlform\YamlFormOptionsInterface $entity */
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['id'] = $entity->id();

    $options = YamlFormOptions::getElementOptions(['#options' => $entity->id()]);
    $options = OptGroup::flattenOptions($options);
    foreach ($options as $key => &$value) {
      if ($key != $value) {
        $value .= ' (' . $key . ')';
      }
    }
    $row['options'] = implode('; ', array_slice($options, 0, 12)) . (count($options) > 12 ? '; ...' : '');

    $row['alter'] = $entity->hasAlterHooks() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
