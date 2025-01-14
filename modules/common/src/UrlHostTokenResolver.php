<?php

namespace Drupal\common;

/**
 * Convert between local file paths and public file URLs.
 *
 * @todo Stop using tokenized host names altogether.
 */
class UrlHostTokenResolver {

  const TOKEN = 'h-o.st';

  const PUBLIC_URI = 'public://';

  /**
   * Get the HTTP server public files URL.
   *
   * @return string|null
   *   The HTTP server public files URL, or NULL in the case of failure.
   */
  public static function getServerPublicFilesUrl(): ?string {
    // Get public file stream.
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = \Drupal::service('stream_wrapper_manager')
      ->getViaUri(self::PUBLIC_URI);
    // Retrieve the URL path for the public stream.
    return $wrapper ? $wrapper->getExternalUrl() : NULL;
  }

  /**
   * Resolve a 'hostified' resource HTTP URL to use the site domain.
   *
   * @param string $resourceUrl
   *   Hostified resource URL.
   *
   * @return string
   *   Resolved resource URL (with site domain).
   */
  public static function resolve(string $resourceUrl): string {
    // Get HTTP server public files URL and extract the host.
    $serverPublicFilesUrl = self::getServerPublicFilesUrl();
    $serverPublicFilesUrl = isset($serverPublicFilesUrl) ? parse_url($serverPublicFilesUrl) : NULL;
    $serverHost = $serverPublicFilesUrl['host'] ?? \Drupal::request()->getHost();
    // Determine whether the hostified token is present in the resource URL, and
    // replace the token if necessary.
    if (substr_count($resourceUrl, self::TOKEN) > 0) {
      $resourceUrl = str_replace(self::TOKEN, $serverHost, $resourceUrl);
    }
    return $resourceUrl;
  }

  /**
   * Resolve an HTTP URL into a public URI.
   *
   * @param string $resourceUrl
   *   Full temporary token URL.
   *
   * @return string
   *   Resolved public file path.
   */
  public static function resolveFilePath(string $resourceUrl): string {
    return urldecode(preg_replace(
      '/^' . preg_quote(self::getServerPublicFilesUrl(), '/') . '/',
      self::PUBLIC_URI, self::resolve($resourceUrl)
    ));
  }

}
