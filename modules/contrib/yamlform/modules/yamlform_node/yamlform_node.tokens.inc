<?php

/**
 * @file
 * Builds placeholder replacement tokens for form nodes.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function yamlform_node_token_info() {
  $types['yamlform_submission'] = [
    'name' => t('Form submissions'),
    'description' => t('Tokens related to form submission.'),
    'needs-data' => 'yamlform_submission',
  ];
  $yamlform_submission['node'] = [
    'name' => t('Node'),
    'description' => t("The node that the form was submitted from."),
    'type' => 'node',
  ];

  return [
    'types' => $types,
    'tokens' => [
      'yamlform_submission' => $yamlform_submission,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function yamlform_node_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'yamlform_submission' && !empty($data['yamlform_submission'])) {
    /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
    $yamlform_submission = $data['yamlform_submission'];
    $source_entity = $yamlform_submission->getSourceEntity();
    if (!$source_entity || (!$source_entity instanceof NodeInterface)) {
      return $replacements;
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node':
          $replacements[$original] = $source_entity->label();
          break;
      }
    }

    if ($entity_tokens = $token_service->findWithPrefix($tokens, 'node')) {
      $replacements += $token_service->generate('node', $entity_tokens, ['node' => $source_entity], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
