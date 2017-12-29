<?php

namespace Drupal\Tests\select_or_other\Unit {

  use Drupal\Core\Form\FormState;
  use Drupal\select_or_other\Element\Buttons;
  use Drupal\Tests\UnitTestCase;
  use ReflectionMethod;

  /**
   * Tests the form element implementation.
   *
   * @group select_or_other
   *
   * @covers \Drupal\select_or_other\Element\Buttons
   */
  class ButtonsElementTest extends UnitTestCase {

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

      // Test single cardinality Buttons.
      $element = $original_element;
      $expected_element = array_merge_recursive($base_expected_element, [
        'select' => [
          '#type' => 'checkboxes',
        ],
        'other' => [
          '#states' => [
            'visible' => [
              ':input[name="' . $element['#name'] . '[select][select_or_other]"]' => ['checked' => TRUE],
            ],
          ],
        ],
      ]);
      $element['#multiple'] = $expected_element['#multiple'] = $expected_element['select']['#multiple'] = TRUE;
      $resulting_element = Buttons::processSelectOrOther($element, $form_state, $form);
      $this->assertArrayEquals($expected_element, $resulting_element);
      $this->assertArrayEquals($resulting_element, $element);

      // Test multiple cardinality Buttons.
      $element = $original_element;
      $expected_element = array_merge_recursive($base_expected_element, [
        'select' => ['#type' => 'radios'],
        'other' => [
          '#states' => [
            'visible' => [
              ':input[name="' . $element['#name'] . '[select]"]' => ['value' => 'select_or_other'],
            ],
          ],
        ],
      ]);
      $resulting_element = Buttons::processSelectOrOther($element, $form_state, $form);
      $this->assertArrayEquals($expected_element, $resulting_element);
      $this->assertArrayEquals($resulting_element, $element);
    }

    /**
     * Make sure radio buttons always have a correct default value.
     */
    public function testEnsureCorrectDefaultValue() {
      $element = [
        'select' => [
          '#type' => 'radios'
        ]
      ];
      $arguments = [ & $element];
      $ensure_correct_default_value = new ReflectionMethod('Drupal\select_or_other\Element\Buttons', 'ensureCorrectDefaultValue');
      $ensure_correct_default_value->setAccessible(TRUE);

      $expected = $element;
      $ensure_correct_default_value->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $element['select']['#default_value'] = 'non_array_default';
      $expected = $element;
      $ensure_correct_default_value->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $element['select']['#default_value'] = ['array_default'];
      $expected['select']['#default_value'] = 'array_default';
      $ensure_correct_default_value->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['select']['#type'] = $element['select']['#type'] = 'checkboxes';
      $expected['select']['#default_value'] = $element['select']['#default_value'] = ['array_default'];
      $ensure_correct_default_value->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);
    }

    /**
     * Make sure the empty option gets added when necessary.
     */
    public function testAddEmptyOption() {
      $element = [
        '#multiple' => TRUE,
        '#required' => TRUE,
        'select' => [
          '#options' => [],
        ],
      ];
      $arguments = [ & $element];
      $add_empty_option = new ReflectionMethod('Drupal\select_or_other\Element\Buttons', 'addEmptyOption');
      $add_empty_option->setAccessible(TRUE);

      $expected = $element;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['#multiple'] = $element['#multiple'] = FALSE;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['#required'] = $element['#required'] = FALSE;
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['#default_value'] = $element['#default_value'] = [];
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['#default_value'] = $element['#default_value'] = ['test'];
      $expected['select']['#options'] = [
        '' => '- None -',
      ];
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

      $expected['#default_value'] = $element['#default_value'] = 'test';
      $expected['select']['#options'] = [
        '' => '- None -',
      ];
      $add_empty_option->invokeArgs(NULL, $arguments);
      $this->assertArrayEquals($expected, $element);

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
