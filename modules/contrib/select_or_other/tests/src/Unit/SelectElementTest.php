<?php

namespace Drupal\Tests\select_or_other\Unit {

  use Drupal\Core\Form\FormState;
  use Drupal\select_or_other\Element\Select;
  use Drupal\Tests\UnitTestCase;
  use ReflectionMethod;

  /**
   * Tests the form element implementation.
   *
   * @group select_or_other
   *
   * @covers \Drupal\select_or_other\Element\Select
   */
  class SelectElementTest extends UnitTestCase {

    /**
     * Tests the processing of a select or other element.
     */
    public function testProcessSelectOrOther() {
      // Test ElementBase.
      // Make the protected method accessible and invoke it.
      $method = new ReflectionMethod('Drupal\select_or_other\Element\ElementBase', 'addOtherOption');
      $method->setAccessible(TRUE);

      $form_state = new FormState();
      $form = [];
      $original_element = $element = [
        '#name' => 'select_or_other',
        '#no_empty_option' => FALSE,
        '#default_value' => 'default',
        '#required' => TRUE,
        '#multiple' => FALSE,
        '#options' => [
          'first_option' => 'First option',
          'second_option' => "Second option"
        ],
      ];

      $base_expected_element = $expected_element = $element + [
          'select' => [
            '#default_value' => $element['#default_value'],
            '#required' => $element['#required'],
            '#multiple' => $element['#multiple'],
            '#options' => $method->invoke(NULL, $element['#options']),
            '#weight' => 10,
          ],
          'other' => [
            '#type' => 'textfield',
            '#weight' => 20,
          ]
        ];

      // Test single cardinality Select.
      $element = $original_element;
      $expected_element = array_merge_recursive($base_expected_element, [
        'select' => ['#type' => 'select'],
        'other' => [
          '#states' => [
            'visible' => [
              ':input[name="' . $element['#name'] . '[select]"]' => ['value' => 'select_or_other'],
            ],
          ],
        ],
      ]);
      $resulting_element = Select::processSelectOrOther($element, $form_state, $form);
      $this->assertArrayEquals($expected_element, $resulting_element);
      $this->assertArrayEquals($resulting_element, $element);

      // Test multiple cardinality Select.
      $element = $original_element;
      $expected_element = array_merge_recursive($base_expected_element, [
        'select' => [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#attached' => [
            'library' => ['select_or_other/multiple_select_states_hack']
          ],
        ],
      ]);
      $element['#multiple'] = $expected_element['#multiple'] = $expected_element['select']['#multiple'] = TRUE;
      $resulting_element = Select::processSelectOrOther($element, $form_state, $form);
      $this->assertArrayEquals($expected_element, $resulting_element);
      $this->assertArrayEquals($resulting_element, $element);

    }

    /**
     * Make sure the empty option gets added when necessary.
     */
    public function testAddEmptyOption() {
      $element = [
        '#required' => TRUE,
        '#default_value' => 'not empty',
      ];

      $empty_option = [
        'select' => [
          '#empty_value' => '',
        ]
      ];

      $arguments = [ & $element];
      $add_empty_option = new ReflectionMethod('Drupal\select_or_other\Element\Select', 'addEmptyOption');
      $add_empty_option->setAccessible(TRUE);

      $expected = $element;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element, 'No empty option is added for required select widgets with a default value.');

      $element['#default_value'] = '';
      $expected = $element + $empty_option;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element, 'Empty option is added for required select widgets without a default value.');

      $element['#default_value'] = '';
      $element['#required'] = FALSE;
      $expected = $element;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element, 'No empty option is added for non-required select widgets without a default value.');

      $element['#default_value'] = 'not empty';
      $expected = $element + $empty_option;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element, 'Empty option is added for non-required select widgets with a default value.');

      $expected['#no_empty_option'] = $element['#no_empty_option'] = FALSE;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $element['#no_empty_option'] = TRUE;
      $expected = $element;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);
    }

  }
}

namespace {
  if (!function_exists('t')) {
    function t($string, array $args = []) {
      return strtr($string, $args);
    }
  }
}
