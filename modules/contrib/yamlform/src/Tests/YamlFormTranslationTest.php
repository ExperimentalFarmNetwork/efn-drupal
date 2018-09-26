<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for form translation.
 *
 * @group YamlForm
 */
class YamlFormTranslationTest extends WebTestBase {

  use YamlFormTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'block', 'yamlform', 'yamlform_examples', 'yamlform_test_translation'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->placeBlocks();

    $admin_user = $this->drupalCreateUser(['access content', 'administer yamlform', 'administer yamlform submission', 'translate configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests form translate.
   */
  public function testTranslate() {
    /** @var \Drupal\yamlform\YamlFormTranslationManagerInterface $translation_manager */
    $translation_manager = \Drupal::service('yamlform.translation_manager');

    $yamlform = YamlForm::load('test_translation');
    $elements_raw = \Drupal::config('yamlform.yamlform.test_translation')->get('elements');
    $elements = Yaml::decode($elements_raw);

    // Check translate tab.
    $this->drupalGet('admin/structure/yamlform/manage/test_translation');
    $this->assertRaw('>Translate<');

    // Check translations.
    $this->drupalGet('admin/structure/yamlform/manage/test_translation/translate');
    $this->assertRaw('<a href="' . base_path() . 'admin/structure/yamlform/manage/test_translation/translate/es/edit">Edit</a>');

    // Check Spanish translations.
    $this->drupalGet('admin/structure/yamlform/manage/test_translation/translate/es/edit');
    $this->assertFieldByName('translation[config_names][yamlform.yamlform.test_translation][title]', 'Prueba: Traducción');
    $this->assertField('translation[config_names][yamlform.yamlform.test_translation][elements]');

    // Check translated form options.
    $this->drupalGet('es/yamlform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Campo de texto</label>');
    $this->assertRaw('<option value="1">Uno</option>');
    $this->assertRaw('<option value="4">Las cuatro</option>');

    // Check that form is not translated into French.
    $this->drupalGet('fr/yamlform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Text field</label>');
    $this->assertRaw('<option value="1">One</option>');
    $this->assertRaw('<option value="4">Four</option>');

    // Check that French config elements returns the default languages elements.
    // Please note: This behavior might change.
    $translation_element = $translation_manager->getConfigElements($yamlform, 'fr', TRUE);
    $this->assertEqual($elements, $translation_element);

    // Create French translation.
    $translation_elements = [
      'textfield' => [
        '#title' => 'French',
        '#custom' => 'custom',
      ],
      'custom' => [
        '#title' => 'Custom',
      ],
    ] + $elements;
    $edit = [
      'translation[config_names][yamlform.yamlform.test_translation][elements]' => Yaml::encode($translation_elements),
    ];
    $this->drupalPostForm('admin/structure/yamlform/manage/test_translation/translate/fr/add', $edit, t('Save translation'));

    // Check French translation.
    $this->drupalGet('fr/yamlform/test_translation');
    $this->assertRaw('<label for="edit-textfield">French</label>');

    // Check French config elements only contains translated properties and
    // custom properties are removed.
    $translation_element = $translation_manager->getConfigElements($yamlform, 'fr', TRUE);
    $this->assertEqual(['textfield' => ['#title' => 'French']], $translation_element);
  }

}
