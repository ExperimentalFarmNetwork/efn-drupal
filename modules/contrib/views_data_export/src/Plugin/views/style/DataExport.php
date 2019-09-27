<?php

namespace Drupal\views_data_export\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rest\Plugin\views\style\Serializer;

/**
 * A style plugin for data export views.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "data_export",
 *   title = @Translation("Data export"),
 *   help = @Translation("Configurable row output for data exports."),
 *   display_types = {"data"}
 * )
 */
class DataExport extends Serializer {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    // CSV options.
    // @todo Can these somehow be moved to a plugin?
    $options['csv_settings']['contains'] = [
      'delimiter' => ['default' => ','],
      'enclosure' => ['default' => '"'],
      'escape_char' => ['default' => '\\'],
      'strip_tags' => ['default' => TRUE],
      'trim' => ['default' => TRUE],
      'encoding' => ['default' => 'utf8'],
      'utf8_bom' => ['default' => FALSE],
    ];

    // XLS options.
    $options['xls_settings']['contains'] = [
      'xls_format' => ['default' => 'Excel2007'],
    ];
    $options['xls_settings']['metadata']['contains'] = [
      // The 'created' and 'modified' elements are not exposed here, as they
      // default to the current time (that the spreadsheet is created), and
      // would probably just confuse the UI.
      'creator' => ['default' => ''],
      'last_modified_by' => ['default' => ''],
      'title' => ['default' => ''],
      'description' => ['default' => ''],
      'subject' => ['default' => ''],
      'keywords' => ['default' => ''],
      'category' => ['default' => ''],
      'manager' => ['default' => ''],
      'company' => ['default' => ''],
      // @todo Expose a UI for custom properties.
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'style_options':

        // Change format to radios instead, since multiple formats here do not
        // make sense as they do for REST exports.
        $form['formats']['#type'] = 'radios';
        $form['formats']['#default_value'] = reset($this->options['formats']);

        $format_options = $this->getFormatOptions();

        if (in_array('csv', $format_options)) {
          // CSV options.
          // @todo Can these be moved to a plugin?
          $csv_options = $this->options['csv_settings'];
          $form['csv_settings'] = [
            '#type' => 'details',
            '#open' => FALSE,
            '#title' => $this->t('CSV settings'),
            '#tree' => TRUE,
            '#states' => [
              'visible' => [':input[name="style_options[formats]"]' => ['value' => 'csv']],
            ],
            'delimiter' => [
              '#type' => 'textfield',
              '#title' => $this->t('Delimiter'),
              '#description' => $this->t('Indicates the character used to delimit fields. Defaults to a comma (<code>,</code>). For tab-separation use <code>\t</code> characters.'),
              '#default_value' => $csv_options['delimiter'],
            ],
            'enclosure' => [
              '#type' => 'textfield',
              '#title' => $this->t('Enclosure'),
              '#description' => $this->t('Indicates the character used for field enclosure. Defaults to a double quote (<code>"</code>).'),
              '#default_value' => $csv_options['enclosure'],
            ],
            'escape_char' => [
              '#type' => 'textfield',
              '#title' => $this->t('Escape character'),
              '#description' => $this->t('Indicates the character used for escaping. Defaults to a backslash (<code>\</code>).'),
              '#default_value' => $csv_options['escape_char'],
            ],
            'strip_tags' => [
              '#type' => 'checkbox',
              '#title' => $this->t('Strip HTML'),
              '#description' => $this->t('Strips HTML tags from CSV cell values.'),
              '#default_value' => $csv_options['strip_tags'],
            ],
            'trim' => [
              '#type' => 'checkbox',
              '#title' => $this->t('Trim whitespace'),
              '#description' => $this->t('Trims whitespace from beginning and end of CSV cell values.'),
              '#default_value' => $csv_options['trim'],
            ],
            'encoding' => [
              '#type' => 'radios',
              '#title' => $this->t('Encoding'),
              '#description' => $this->t('Determines the encoding used for CSV cell values.'),
              '#options' => [
                'utf8' => $this->t('UTF-8'),
              ],
              '#default_value' => $csv_options['encoding'],
            ],
            'utf8_bom' => [
              '#type' => 'checkbox',
              '#title' => $this->t('Include unicode signature (<a href="@bom" target="_blank">BOM</a>).', [
                '@bom' => 'https://www.w3.org/International/questions/qa-byte-order-mark'
              ]),
              '#default_value' => $csv_options['utf8_bom'],
            ],
          ];
        }

        if (in_array('xls', $format_options)) {
          // XLS options.
          // @todo Can these be moved to a plugin?
          $xls_options = $this->options['xls_settings'];
          $form['xls_settings'] = [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => $this->t('XLS settings'),
            '#tree' => TRUE,
            '#states' => [
              'visible' => [
                ':input[name="style_options[formats]"]' => [
                  ['value' => 'xls'],
                  ['value' => 'xlsx'],
                ],
              ],
            ],
            'xls_format' => [
              '#type' => 'select',
              '#title' => $this->t('Format'),
              '#options' => [
                // @todo Add all PHPExcel supported formats.
                'Excel2007' => $this->t('Excel 2007'),
                'Excel5' => $this->t('Excel 5'),
              ],
              '#default_value' => $xls_options['xls_format'],
            ],
          ];

          $metadata = !empty($xls_options['metadata']) ? array_filter($xls_options['metadata']) : [];

          // XLS metadata.
          $form['xls_settings']['metadata'] = [
            '#type' => 'details',
            '#title' => $this->t('Document metadata'),
            '#open' => TRUE,
          ];

          $xls_fields = [
            'creator' => $this->t('Author/creator name'),
            'last_modified_by' => $this->t('Last modified by'),
            'title' => $this->t('Title'),
            'description' => $this->t('Description'),
            'subject' => $this->t('Subject'),
            'keywords' => $this->t('Keywords'),
            'category' => $this->t('Category'),
            'manager' => $this->t('Manager'),
            'company' => $this->t('Company'),
          ];

          foreach ($xls_fields as $xls_field_key => $xls_field_title) {
            $form['xls_settings']['metadata'][$xls_field_key] = [
              '#type' => 'textfield',
              '#title' => $xls_field_title,
            ];

            if (isset($xls_options['metadata'][$xls_field_key])) {
              $form['xls_settings']['metadata']['#default_value'] = $xls_options['metadata'][$xls_field_key];
            }
          }
        }

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Transform the formats back into an array.
    $format = $form_state->getValue(['style_options', 'formats']);
    $form_state->setValue(['style_options', 'formats'], [$format => $format]);
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo This should implement AttachableStyleInterface once
   * https://www.drupal.org/node/2779205 lands.
   */
  public function attachTo(array &$build, $display_id, Url $url, $title) {
    // @todo This mostly hard-codes CSV handling. Figure out how to abstract.

    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    if ($pager = $this->view->getPager()) {
      $url_options['query']['page'] = $pager->getCurrentPage();
    }
    $url_options['absolute'] = TRUE;
    if (!empty($this->options['formats'])) {
      $url_options['query']['_format'] = reset($this->options['formats']);
    }

    $url = $url->setOptions($url_options)->toString();

    // Add the CSV icon to the view.
    $type = $this->displayHandler->getContentType();
    $this->view->feedIcons[] = [
      '#theme' => 'export_icon',
      '#url' => $url,
      '#type' => mb_strtoupper($type),
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => [
              Html::cleanCssIdentifier($type) . '-feed',
              'views-data-export-feed',
            ],
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'views_data_export/views_data_export',
        ],
      ],
    ];

    // Attach a link to the CSV feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => $this->displayHandler->getMimeType(),
      'title' => $title,
      'href' => $url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildSortPost() {
    $query = $this->view->getRequest()->query;
    $sort_field = $query->get('order');

    if (empty($sort_field) || empty($this->view->field[$sort_field])) {
      return;
    }

    // Ensure sort order is valid.
    $sort_order = strtolower($query->get('sort'));
    if (empty($sort_order) || ($sort_order != 'asc' && $sort_order != 'desc')) {
      $sort_order = 'asc';
    }

    // Tell the field to click sort.
    $this->view->field[$sort_field]->clickSort($sort_order);
  }

}
