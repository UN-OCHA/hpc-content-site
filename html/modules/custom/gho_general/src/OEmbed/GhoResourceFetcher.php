<?php

namespace Drupal\gho_general\OEmbed;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Drupal\media\OEmbed\ResourceFetcher;

/**
 * Fetches and caches oEmbed resources.
 */
class GhoResourceFetcher extends ResourceFetcher {

  /**
   * {@inheritdoc}
   *
   * Same code as Drupal\media\OEmbed\ResourceFetcher::fetchResource() with the
   * added handling of youtube responses with a text/html content type and check
   * on the decoded data.
   */
  public function fetchResource($url) {
    $cache_id = "media:oembed_resource:$url";

    $cached = $this->cacheBackend->get($cache_id);
    if ($cached) {
      return $this->createResource($cached->data, $url);
    }

    try {
      $response = $this->httpClient->get($url);
    }
    catch (RequestException $e) {
      throw new ResourceException('Could not retrieve the oEmbed resource.', $url, [], $e);
    }

    [$format] = $response->getHeader('Content-Type');
    $content = (string) $response->getBody();

    if (strstr($format, 'text/xml') || strstr($format, 'application/xml')) {
      $data = $this->parseResourceXml($content, $url);
    }
    elseif (strstr($format, 'text/javascript') || strstr($format, 'application/json')) {
      $data = Json::decode($content);
    }
    // Sometimes the youtube oembed proxy returns the wrong content-type while
    // the body is the correct JSON.
    elseif (strstr($format, 'text/html') && strpos($url, 'youtube') !== FALSE) {
      $data = Json::decode($content);
    }
    // If the response is neither XML nor JSON, we are in bat country.
    else {
      throw new ResourceException('The fetched resource did not have a valid Content-Type header.', $url);
    }

    // Ensure we have at least a semblance of correct data before caching it.
    // Json::decode() doesn't throw an exception when failing to parse the data.
    // ResourceFetcher::createResource() will do additional checks later.
    if (!is_array($data)) {
      throw new ResourceException('The fetched resource could not be parsed.', $url);
    }

    $this->cacheBackend->set($cache_id, $data);

    return $this->createResource($data, $url);
  }

}
