<?php

namespace Drupal\yamlform\Tests;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\simpletest\WebTestBase;
use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for form date elements.
 *
 * @group YamlForm
 */
class YamlFormElementDateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['yamlform', 'yamlform_test'];

  /**
   * Test date element.
   */
  public function testDateElement() {

    /* Default value handling */

    $yamlform_dates = YamlForm::load('test_element_dates');

    // Check '#format' values.
    $this->drupalGet('yamlform/test_element_dates');
    $this->assertFieldByName('date_default', '2009-08-18');
    $this->assertFieldByName('datetime_default[date]', '2009-08-18');
    $this->assertFieldByName('datetime_default[time]', '16:00:00');
    $this->assertFieldByName('datelist_default[month]', '8');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $yamlform_dates->getSubmissionForm();
    $this->assert(is_string($form['elements']['date_elements']['date_default']['#default_value']), 'date_default #default_value is a string.');
    $this->assert($form['elements']['datetime_elements']['datetime_default']['#default_value'] instanceof DrupalDateTime, 'datelist_default #default_value instance of \Drupal\Core\Datetime\DrupalDateTime.');
    $this->assert($form['elements']['datelist_elements']['datelist_default']['#default_value'] instanceof DrupalDateTime, 'datelist_default #default_value instance of \Drupal\Core\Datetime\DrupalDateTime.');

    /* Date Validation */

    // Check date #max validation.
    $edit = ['date_min_max' => '2010-08-18'];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">date (min/max)</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check date #min validation.
    $edit = ['date_min_max' => '2006-08-18'];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">date (min/max)</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check dynamic date.
    $this->drupalGet('yamlform/test_element_dates');
    $min = \Drupal::service('date.formatter')->format(strtotime('-1 year'), 'html_date');
    $max = \Drupal::service('date.formatter')->format(strtotime('+1 year'), 'html_date');
    $default_value = \Drupal::service('date.formatter')->format(strtotime('now'), 'html_date');
    $this->assertRaw('<input min="' . $min . '" max="' . $max . '" type="date" data-drupal-selector="edit-date-min-max-dynamic" aria-describedby="edit-date-min-max-dynamic--description" data-drupal-date-format="Y-m-d" id="edit-date-min-max-dynamic" name="date_min_max_dynamic" value="' . $default_value . '" class="form-date" />');

    /* Datetime Validation */

    // Check datetime #max validation.
    $edit = ['datetime_min_max[date]' => '2010-08-18'];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime (min/max)</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datetime #min validation.
    $edit = ['datetime_min_max[date]' => '2006-08-18'];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime (min/max)</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    /* Datelist Validation */

    // Check datelist #max validation.
    $edit = [
      'datelist_min_max[year]' => '2010',
      'datelist_min_max[month]' => '8',
      'datelist_min_max[day]' => '18',
    ];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datelist (min/max)</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datelist #min validation.
    $edit = [
      'datelist_min_max[year]' => '2006',
      'datelist_min_max[month]' => '8',
      'datelist_min_max[day]' => '18',
    ];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datelist (min/max)</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    /* Time element and validation */

    // Check time element.
    $this->drupalGet('yamlform/test_element_dates');
    $this->assertRaw('<label for="edit-time-12-hour">time 12 hour</label>');
    $this->assertRaw('<input data-drupal-selector="edit-time-12-hour" data-yamlform-time-format="g:i A" type="time" id="edit-time-12-hour" name="time_12_hour" value="14:00" size="10" class="form-time" />');

    // Check time validation.
    $edit = ['time_24_hour' => 'not-valid'];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time 24 hour</em> must be a valid time.');

    // Check time #max validation.
    $edit = [
      'time_min_max' => '12:00',
    ];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time (min/max)</em> must be on or after <em class="placeholder">14:00</em>.');

    // Check time #min validation.
    $edit = [
      'time_min_max' => '22:00',
    ];
    $this->drupalPostForm('yamlform/test_element_dates', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time (min/max)</em> must be on or before <em class="placeholder">18:00</em>.');
  }

}
