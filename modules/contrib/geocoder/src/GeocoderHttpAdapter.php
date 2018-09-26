<?php

namespace Drupal\geocoder;

use GuzzleHttp\ClientInterface;
use Ivory\HttpAdapter\AbstractHttpAdapter;
use Ivory\HttpAdapter\Message\InternalRequestInterface;

/**
 * Extends AbstractHttpAdapter to use Guzzle.
 */
class GeocoderHttpAdapter extends AbstractHttpAdapter {
  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'geocoder_http_adapter';
  }

  /**
   * Creates an HTTP adapter.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   */
  public function __construct(ClientInterface $httpClient) {
    parent::__construct();
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  protected function sendInternalRequest(InternalRequestInterface $internalRequest) {
    return $this->httpClient->request($internalRequest->getMethod(), (string) $internalRequest->getUri(), [
      'headers' => $this->prepareHeaders($internalRequest, FALSE, FALSE),
    ]);
  }

}
