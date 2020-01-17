<?php

/**
 * @file
 * Provides reports pages for Sendgrid Reports module.
 */

/**
 * Global stats page.
 * @return array
 */
function sendgrid_integration_reports_global() {
  $stats = sendgrid_integration_reports_get_stats_global();
  $settings = [];
  foreach ($stats['global'] as $items) {
    $settings['sendgrid_integration_reports']['global'][] = [
      'date' => $items['date'],
      'opens' => $items['opens'],
      'clicks' => $items['clicks'],
      'delivered' => $items['delivered'],
      'spam_reports' => $items['spam_reports'],
      'spam_report_drops' => $items['spam_report_drops'],
    ];
  }
  $path = drupal_get_path('module', 'sendgrid_integration_reports');
  $render = [
    '#attached' => [
      'js' => [
        [
          'data' => 'https://www.google.com/jsapi',
          'type' => 'external',
        ],
        $path . '/sendgrid_integration_reports.js',
        [
          'data' => $settings,
          'type' => 'setting',
        ],
      ],
    ],
    'message' => [
      '#markup' => t(
        'The following reports are the from the Global Statistics provided by Sendgrid. For more comprehensive data, please visit your !dashboard. !cache to ensure the data is current. !settings to alter the time frame of this data.',
        [
          '!dashboard' => l(t('Sendgrid Dashboard'), 'https://app.sengrid.com/'),
          '!cache' => l(t('Clear your cache'), 'admin/config/development/performance'),
          '!settings' => l(t('Change your settings'), 'admin/config/services/sendgrid/reports'),
        ]
      ),
    ],
    'volume' => [
      '#prefix' => '<h2>' . t('Sending Volume') . '</h2>',
      '#markup' => '<div id="sendgrid-global-volume-chart"></div>',
    ],
    'spam' => [
      '#prefix' => '<h2>' . t('Spam Reports') . '</h2>',
      '#markup' => '<div id="sendgrid-global-spam-chart"></div>',
    ],
  ];
  $browserstats = sendgrid_integration_reports_get_stats_browser();
  $rows = [];
  foreach ($browserstats as $key => $value) {
    $rows[] = [
      $key,
      $value,
    ];
  }
  $headerbrowser = [
    t('Browser'),
    t('Click Count'),
  ];
  $render['browsers'] = [
    '#prefix' => '<h2>' . t('Browser Statistics') . '</h2>',
    '#theme' => 'table',
    '#header' => $headerbrowser,
    '#rows' => $rows,
    'attributes' => ['width' => '75%'],
  ];

  $devicestats = sendgrid_integration_reports_get_stats_devices();
  $rowsdevices = [];
  foreach ($devicestats as $key => $value) {
    $rowsdevices[] = [
      $key,
      $value,
    ];
  }
  $headerdevices = [
    t('Device'),
    t('Open Count'),
  ];
  $render['devices'] = [
    '#prefix' => '<h2>' . t('Device Statistics') . '</h2>',
    '#theme' => 'table',
    '#header' => $headerdevices,
    '#rows' => $rowsdevices,
    'attributes' => ['width' => '75%'],
  ];

  return $render;
}
