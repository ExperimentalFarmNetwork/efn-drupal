<?php

namespace Drupal\yamlform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\yamlform\Utility\YamlFormArrayHelper;
use Drupal\yamlform\Utility\YamlFormElementHelper;

/**
 * Defines a class to translate form elements.
 */
class YamlFormTranslationManager implements YamlFormTranslationManagerInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Form element manager.
   *
   * @var \Drupal\yamlform\YamlFormElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a YamlFormTranslationManager object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, YamlFormElementManagerInterface $element_manager) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigElements(YamlFormInterface $yamlform, $langcode, $reset = FALSE) {
    // Note: Below code return the default languages elements for missing
    // translations.
    $config_override_language = $this->languageManager->getConfigOverrideLanguage();
    $config_name = 'yamlform.yamlform.' . $yamlform->id();

    // Reset cached config.
    if ($reset) {
      $this->configFactory->reset($config_name);
    }

    $this->languageManager->setConfigOverrideLanguage($this->languageManager->getLanguage($langcode));
    $elements = $this->configFactory->get($config_name)->get('elements');
    $this->languageManager->setConfigOverrideLanguage($config_override_language);
    return $elements ? Yaml::decode($elements) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseElements(YamlFormInterface $yamlform) {
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $config_elements = $this->getConfigElements($yamlform, $default_langcode);
    $elements = YamlFormElementHelper::getFlattened($config_elements);
    $translatable_properties = YamlFormArrayHelper::addPrefix($this->elementManager->getTranslatableProperties());
    foreach ($elements as $element_key => &$element) {
      foreach ($element as $property_key => $property_value) {
        $translatable_property_key = $property_key;
        // If translatable property key is a sub element (ex: subelement__title)
        // get the sub element's translatable property key.
        if (preg_match('/^.*__(.*)$/', $translatable_property_key, $match)) {
          $translatable_property_key = '#' . $match[1];
        }

        if (in_array($translatable_property_key, ['#options', '#answers']) && is_string($property_value)) {
          // Unset options and answers that are form option ids.
          unset($element[$property_key]);
        }
        elseif (!isset($translatable_properties[$translatable_property_key])) {
          // Unset none translatble properties.
          unset($element[$property_key]);
        }
      }
      if (empty($element)) {
        unset($elements[$element_key]);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElements(YamlFormInterface $yamlform) {
    $elements = $this->getBaseElements($yamlform);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationElements(YamlFormInterface $yamlform, $langcode) {
    $elements = $this->getSourceElements($yamlform);
    $translation_elements = $this->getConfigElements($yamlform, $langcode);
    if ($elements == $translation_elements) {
      return $elements;
    }
    YamlFormElementHelper::merge($elements, $translation_elements);
    return $elements;
  }

}
