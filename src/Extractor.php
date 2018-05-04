<?php

namespace Drupal\webform_tracking;

class Extractor {
  public static $parameters = [
    'user_id' => '',
    'tags' => [],
    'external_referer' => '',
    'source' => '',
    'medium' => '',
    'version' => '',
    'other' => '',
    'term' => '',
    'campaign' => '',
    'refsid' => '',
  ];

  protected $cookieData;
  protected $query;

  public static function fromEnv() {
    $cookie_data = [];
    if (isset($_COOKIE['webform_tracking'])) {
      $cookie_data = drupal_json_decode($_COOKIE['webform_tracking']);
    }
    return new static($cookie_data, drupal_get_query_parameters());
  }

  public function __construct($cookie_data, $query) {
    $this->cookieData = $cookie_data;
    $this->query = $query;
  }

  protected function getIP() {
    return ip_address();
  }

  /**
   * Returns a trimmed IP address.
   *
   * Trims the rightmost bits to anonymize an IP address.
   *
   * If an invalid IP address is given, return '0.0.0.0'.
   */
  protected function filterIP($ip_address, $significant_bits = 16) {
    if (!variable_get('webform_tracking_track_ip_address', FALSE)) {
      return '';
    }

    if ($significant_bits > 32) {
      $significant_bits = 32;
    }
    elseif ($significant_bits < 0) {
      $significant_bits = 0;
    }
    // construct an integer with all x bits set to 1, then shift left the
    // difference to fill right with 0
    $bitmask = (2**$significant_bits - 1) << (32 - $significant_bits);
    $ip_as_long = ip2long($ip_address);
    // when the IP is invalid
    if ($ip_as_long === FALSE) {
      return '0.0.0.0';
    }
    $trimmed_ip = long2ip($ip_as_long & $bitmask);
    return $trimmed_ip;
  }

  protected function getCountry($ip) {
    if (function_exists('geoip_country_code_by_name')) {
      // Use @, see: https://bugs.php.net/bug.php?id=59753
      $country = @geoip_country_code_by_name($ip);

      // Check if this really is a ISO country code.
      include_once DRUPAL_ROOT . '/includes/locale.inc';
      if (isset(country_get_list()[$country])) {
        return $country;
      }
    }
  }

  public function extractParameters($data) {
    $parameters = static::$parameters;
    foreach (static::$parameters as $name => $default) {
      if ($name != 'tags') {
        if (isset($data[$name]) && !is_array($data[$name])) {
          $parameters[$name] = check_plain($data[$name]);
        }
      }
    }
    if (isset($data['tags']) && is_array($data['tags'])) {
      foreach ($data['tags'] as $t) {
        if (!is_array($t)) {
          $parameters['tags'][] = check_plain($t);
        }
      }
    }
    $parameters['tags'] = serialize(array_unique($parameters['tags']));

    if (!$parameters['user_id']) {
      $parameters['user_id'] = hash('adler32', rand() . microtime());
    }
    $parameters['refsid'] = $parameters['refsid'] ? (int) $parameters['refsid'] : NULL;

    return $parameters;
  }

  protected function urls($cookie_history) {
    $history = [];
    if ($cookie_history) {
      foreach ($cookie_history as $url) {
        if (!is_array($url)) {
          $history[] = check_plain($url);
        }
      }
    }
    if (!$history) {
      $history[] = url(NULL, [
        'absolute' => TRUE,
        'query' => $this->query,
      ]);
    }
    $length = count($history);

    return [
      'entry_url' => $history[0],
      // The only situation when $history should be < 3 appears if the user opens
      // the form directly, in this case referer and form_url are the same.
      'referer'   => $history[max(0, $length - 2)],
      'form_url'  => $history[max(0, $length - 1)],
    ];
  }

  /**
   * Save tracking variables for a submission.
   *
   * @return array
   *   The updated cookie data.
   */
  public function saveVars($submission) {
    $cookie_data = $this->cookieData + [
      'history' => [],
    ];

    $parameters = $this->extractParameters($cookie_data);

    $ip = $this->getIP();
    $significant_bits = (int) variable_get('webform_tracking_significant_ip_address_bits', 16);
    $server_data = array(
      'ip_address' => $this->filterIP($ip, $significant_bits),
      'country' => $this->getCountry($ip),
    );

    $urls = $this->urls($cookie_data['history']);

    $data = array(
      'nid' => $submission->nid,
      'sid' => $submission->sid,
    ) + $urls + $parameters + $server_data;
    $submission->tracking = (object) $data;

    db_insert('webform_tracking')->fields($data)->execute();
    $cookie_data['user_id'] = $parameters['user_id'];
    return $cookie_data;
  }
}
