<?php
/**
 * @file
 * Contains unit tests for the ListWidget.
 */

namespace Drupal\Tests\select_or_other\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\select_or_other\Plugin\Field\FieldWidget\ListWidget;
use Drupal\select_or_other\Plugin\Field\FieldWidget\WidgetBase;
use ReflectionMethod;

/**
 * Tests the form element implementation.
 *
 * @group select_or_other
 *
 * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\ListWidget
 */
class ListWidgetTest extends UnitTestBase {

  /**
   * @return string
   *   The fully qualified class name of the subject under test.
   */
  protected function getTestedClassName() {
    return 'Drupal\select_or_other\Plugin\Field\FieldWidget\ListWidget';
  }

  /**
   * Test if defaultSettings() returns the correct keys.
   */
  public function testGetOptions() {
    $expected = [1, 2];
    $options_provider = $this->getMockForAbstractClass('Drupal\Core\TypedData\OptionsProviderInterface');
    $options_provider->method('getSettableOptions')->willReturn($expected);

    $storage_definition = $this->getMockForAbstractClass('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $storage_definition->method('getOptionsProvider')
      ->willReturn($options_provider);

    $field_definition = $this->getMockForAbstractClass('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->method('getFieldStorageDefinition')
      ->willReturn($storage_definition);
    $constructor_arguments = ['', '', $field_definition, [], []];
    $mock = $this->mockBuilder->setConstructorArgs($constructor_arguments)
      ->setMethods([
        'getColumn'
      ])
      ->getMock();
    $mock->method('getColumn')->willReturn(['column']);

    $get_options = new ReflectionMethod($mock, 'getOptions');
    $get_options->setAccessible(TRUE);

    $options = $get_options->invoke($mock, $this->getMockForAbstractClass('Drupal\Core\Entity\FieldableEntityInterface'));
    $this->assertArrayEquals($expected, $options);
  }

  /**
   * Test if formElement() adds the expected information.
   */
  public function testFormElement() {
    list($parent, $mock) = $this->getBasicMocks();
    /** @var ListWidget $mock */
    /** @var WidgetBase $parent */
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemListInterface');
    $delta = NULL;
    $element = [];
    $form = [];
    $form_state = new FormState();

    $parent_result = $parent->formElement($items, $delta, $element, $form, $form_state);
    $result = $mock->formElement($items, $delta, $element, $form, $form_state);
    $added = array_diff_key($result, $parent_result);

    $expected = [
      '#merged_values' => TRUE,
    ];

    $this->assertArrayEquals($expected, $added);
  }

  /**
   * @test
   */
  public function massageFormValuesReturnsValuesPassedToIt() {
    $sut = $this->getNewSubjectUnderTest();
    $form = [];
    $form_state = new FormState();
    /** @var ListWidget $mock */
    $test_values = [
      [],
      ['value'],
      ['multiple', 'values'],
    ];

    foreach ($test_values as $values) {
      $result = $sut->massageFormValues($values, $form, $form_state);
      $this->assertArrayEquals($values, $result);
    }
  }

  /**
   * @test
   */
  public function massageFormValuesRemovesSelectValueIfPresent() {
    $sut = $this->getNewSubjectUnderTest();
    $form = [];
    $form_state = new FormState();
    /** @var ListWidget $mock */
    $result = $sut->massageFormValues(['select' => 'test'], $form, $form_state);
    $this->assertArrayEquals([], $result);
  }

  /**
   * @test
   */
  public function massageFormValuesRemovesOtherValueIfPresent() {
    $sut = $this->getNewSubjectUnderTest();
    $form = [];
    $form_state = new FormState();
    /** @var ListWidget $mock */
    $result = $sut->massageFormValues(['other' => 'test'], $form, $form_state);
    $this->assertArrayEquals([], $result);
  }

  /**
   * @test
   * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\ListWidget::extractNewValues
   * @covers Drupal\select_or_other\Plugin\Field\FieldWidget\ListWidget::AddNewValuesToAllowedValues
   */
  public function massageFormValuesAddsNewValuesToAllowedValues() {
    $allowed_values = ['t' => 'test'];
    $field_definition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->method('getSetting')->willReturn($allowed_values);
    $sut = $this->getNewSubjectUnderTest($field_definition);

    $field_storage_config = $this->getMockForAbstractClass('\Drupal\field\FieldStorageConfigInterface');
    $field_storage_config->expects($this->once())->method('setSetting')->willReturnSelf();
    $field_storage_config->expects($this->once())->method('save');
    $entity_storage_methods = ['load' => $field_storage_config,];
    $entity_type_manager_methods = ['getStorage' => $this->getMockForAbstractClassWithMethods('\Drupal\Core\Entity\EntityStorageInterface', $entity_storage_methods),];
    $entity_type_manager_mock = $this->getMockForAbstractClassWithMethods('\Drupal\Core\Entity\EntityTypeManagerInterface', $entity_type_manager_methods);
    $this->registerServiceWithContainerMock('entity_type.manager', $entity_type_manager_mock);

    $form = [];
    $form_state = new FormState();

    // First invocation does not call setSetting or save.
    $sut->massageFormValues(['t'], $form, $form_state);
    // Second invocation calls setSetting and save.
    $sut->massageFormValues(['t', 'est'], $form, $form_state);
  }

  protected function getNewSubjectUnderTest(FieldDefinitionInterface $fieldDefinition = NULL) {
    $widget_id = 'widget_id';
    $plugin_definition = 'plugin_definition';
    if (empty($fieldDefinition)) {
      $fieldDefinition = $this->getMockForAbstractClass('\Drupal\Core\Field\FieldDefinitionInterface');
    }
    $settings = [];
    $third_party_settings = [];
    return new ListWidget($widget_id, $plugin_definition, $fieldDefinition, $settings, $third_party_settings);
  }

}
