<?php

namespace Drupal\yamlform\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\yamlform\Element\YamlFormMessage;
use Drupal\yamlform\Entity\YamlFormOptions;
use Drupal\yamlform\YamlFormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for form element.
 */
class YamlFormElementController extends ControllerBase {

  /**
   * Returns response for message close using user or state storage.
   *
   * @param string $storage
   *   Mechanism that the message state should be stored in, user or state.
   * @param string $id
   *   The unique id of the message.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An empty AJAX response.
   *
   * @throws \Exception
   *   Throws exception is storage is not set to 'user' or 'state'.
   *
   * @see \Drupal\yamlform\Element\YamlFormMessage::setClosed
   */
  public function close($storage, $id) {
    if (!in_array($storage, ['user', 'state'])) {
      throw new \Exception('Undefined storage mechanism for YAML Form close message.');
    }
    YamlFormMessage::setClosed($storage, $id);
    return new AjaxResponse();
  }

  /**
   * Returns response for the element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   * @param string $key
   *   Form element key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, YamlFormInterface $yamlform, $key) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';
    if ($q == '') {
      return new JsonResponse([]);
    }

    // Get the initialized form element.
    $element = $yamlform->getElement($key);
    if (!$element) {
      return new JsonResponse([]);
    }

    // Set default autocomplete properties.
    $element += [
      '#autocomplete_existing' => FALSE,
      '#autocomplete_items' => [],
      '#autocomplete_match' => 3,
      '#autocomplete_limit' => 10,
      '#autocomplete_match_operator' => 'CONTAINS',
    ];

    // Check minimum number of characters.
    if (Unicode::strlen($q) < (int) $element['#autocomplete_match']) {
      return new JsonResponse([]);
    }

    $matches = [];

    // Get existing matches.
    if (!empty($element['#autocomplete_existing'])) {
      $matches += $this->getMatchesFromExistingValues($q, $yamlform->id(), $key, $element['#autocomplete_match_operator'], $element['#autocomplete_limit']);
    }

    // Get items (aka options) matches.
    if (!empty($element['#autocomplete_items'])) {
      $element['#options'] = $element['#autocomplete_items'];
      $options = YamlFormOptions::getElementOptions($element);
      $matches += $this->getMatchesFromOptions($q, $options, $element['#autocomplete_match_operator'], $element['#autocomplete_limit']);
    }

    // Sort matches and enforce the limit.
    if ($matches) {
      ksort($matches);
      $matches = array_values($matches);
      $matches = array_slice($matches, 0, $element['#autocomplete_limit']);
    }

    return new JsonResponse($matches);
  }

  /**
   * Get matches from existing submission values.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param string $yamlform_id
   *   The form id.
   * @param string $key
   *   The element's key.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   * @param int $limit
   *   Limit number of matches.
   *
   * @return array
   *   An array of matches.
   */
  protected function getMatchesFromExistingValues($q, $yamlform_id, $key, $operator = 'CONTAINS', $limit = 10) {
    // Query form submission for existing values.
    $query = Database::getConnection()->select('yamlform_submission_data')
      ->fields('yamlform_submission_data', ['value'])
      ->condition('yamlform_id', $yamlform_id)
      ->condition('name', $key)
      ->condition('value', ($operator == 'START_WITH') ? "$q%" : "%$q%", 'LIKE')
      ->orderBy('value');
    if ($limit) {
      $query->range(0, $limit);
    }

    // Convert query results values to matches array.
    $values = $query->execute()->fetchCol();
    $matches = [];
    foreach ($values as $value) {
      $matches[$value] = ['value' => $value, 'label' => $value];
    }
    return $matches;
  }

  /**
   * Get matches from options.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param array $options
   *   An associative array of form options.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   * @param int $limit
   *   Limit number of matches.
   *
   * @return array
   *   An array of matches sorted by label.
   */
  protected function getMatchesFromOptions($q, array $options, $operator = 'CONTAINS', $limit = 10) {
    // Make sure options are populated.
    if (empty($options)) {
      return [];
    }

    $matches = [];

    // Filter and convert options to autocomplete matches.
    $this->getMatchesFromOptionsRecursive($q, $options, $matches, $operator);

    // Sort matches.
    ksort($matches);

    // Apply match limit.
    if ($limit) {
      $matches = array_slice($matches, 0, $limit);
    }

    return array_values($matches);
  }

  /**
   * Get matches from options recursive.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param array $options
   *   An associative array of form options.
   * @param array $matches
   *   An associative array of autocomplete matches.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   */
  protected function getMatchesFromOptionsRecursive($q, array $options, array &$matches, $operator = 'CONTAINS') {
    foreach ($options as $value => $label) {
      if (is_array($label)) {
        $this->getMatchesFromOptionsRecursive($q, $label, $matches, $operator);
        continue;
      }

      // Cast TranslatableMarkup to string.
      $label = (string) $label;

      if ($operator == 'STARTS_WITH' && stripos($label, $q) === 0) {
        $matches[$label] = [
          'value' => $label,
          'label' => $label,
        ];
      }
      // Default to CONTAINS even when operator is empty.
      elseif (stripos($label, $q) !== FALSE) {
        $matches[$label] = [
          'value' => $label,
          'label' => $label,
        ];
      }

    }
  }

}
