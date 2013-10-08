<?php

/**
 * @file
 * Master Page Service manager class.
 */

class MasterPageService {
  protected $baseUrl;

  /**
   * MPS Constructor.
   */
  public function __construct() {
    $this->baseUrl = variable_get('mps_base_url', MPS_DEFAULT_BASE_URL);

    return $this;
  }

  /**
   * Constructs the request.
   */
  public function buildRequest($params) {
    global $user;

    $request = array(
      'url' => $this->baseUrl . MPS_DEFAULT_JSON_API_PATH,
      'data' => array(),
    );

    $path = $params['path'] ? drupal_get_normal_path($params['path']) : variable_get('site_frontpage', 'node');
    $menu_item = menu_get_item($path);
    $object = isset($menu_item['page_arguments'][0]) ? $menu_item['page_arguments'][0] : new stdClass();

    $defaults = variable_get('mps_mapping_defaults', array());

    // Loop through the mapping defaults and build up the request parameter
    // array.  Pass each default through the token filter, providing a node
    // or taxonomy object (depending) and a user object.
    foreach ($defaults as $key => $value) {
      $data = array('user' => $user);
      if (isset($object->nid)) {
        $data['node'] = $object;
      }
      elseif (isset($object->tid)) {
        $data['term'] = $object;
      }
      if ($replaced = token_replace($value, $data, array('clear' => TRUE))) {
        $request['data'][$key] = $replaced;
      }
    }

    return $request;
  }

  /**
   * Executes a request to MPS.
   */
  public function executeRequest($request) {
    $url = url($request['url'], array('query' => $request['data']));
    $response = drupal_http_request($url);
    $response->request_url = $url;
    return $response;
  }

  /**
   * Retrieve the configured available adunit regions from MPS.
   */
  public function getAdUnitRegions() {
    $cid = 'mps_adunit_regions';

    $cached = cache_get($cid);
    if (isset($cached->data)) {
      return $cached->data;
    }
    else {
      $descr = $this->getServiceDescription();
      $blocks = array();
      foreach ($descr['adunits'] as $key) {
        $blocks[$this->cleanComponentIdentifier($key)] = $key;
      }
      if (!$this->inDebugMode()) {
        cache_set($cid, $blocks);
      }
      return $blocks;
    }
  }

  /**
   * Retrieve the configured available page component regions from MPS.
   */
  public function getPageComponents() {
    $cid = 'mps_component_regions';

    $cached = cache_get($cid);
    if (isset($cached->data)) {
      return $cached->data;
    }
    else {
      $descr = $this->getServiceDescription();
      $blocks = array();
      foreach ($descr['components'] as $key) {
        $blocks[$this->cleanComponentIdentifier($key)] = $key;
      }
      if (!$this->inDebugMode()) {
        cache_set($cid, $blocks);
      }
      return $blocks;
    }
  }

  /**
   * Scrub component IDs of strange unwanted characters.
   */
  protected function cleanComponentIdentifier($identifier) {
    $filters = array(' ' => '_', '_' => '_', '/' => '_', '[' => '', ']' => '');
    return drupal_clean_css_identifier($identifier, $filter = $filters);
  }

  /**
   * Retrieves from MPS a description of its API and available items.
   */
  protected function getServiceDescription() {
    $description = &drupal_static(__FUNCTION__);

    if (empty($description)) {
      $url = $this->baseUrl . variable_get('mps_api_descriptor', MPS_DEFAULT_API_DESCRIPTOR) . '/' . mps_get_mapping_default('site', MPS_DEFAULT_SITE_ID);
      if (!valid_url($url, TRUE)) {
        watchdog('MPS', 'Bad service URL: %url', array('%url', $url), WATCHDOG_ERROR);
        if ($this->inDebugMode()) {
          drupal_set_message('MPS Bad descriptor url: ' . $url, 'error');
        }
      }
      if ($this->inDebugMode()) {
        drupal_set_message('MPS Descriptor call to ' . $url);
      }
      $response = drupal_http_request($url);
      if (isset($response->data)) {
        $description = drupal_json_decode($response->data);
      }
      else {
        watchdog('MPS', "Couldn't get site description from MPS", array(), WATCHDOG_ERROR);
        if ($this->inDebugMode()) {
          drupal_set_message("MPS didn't return any data from its descriptor service.", 'error');
        }
      }
    }

    return $description;
  }

  /**
   * Checks to see if a path has been excluded from MPS processing.
   */
  public function isExcludedFor($path = NULL) {
    return mps_path_is_excluded($path);
  }

  /**
   * Checks to see if MPS is in debug mode.
   */
  protected function inDebugMode() {
    return mps_in_debug_mode();
  }

  /*
   * From Rich Rhee via http://pastebin.com/Fp2fuPbm:
   */

  /**
   * Formats a key for the CAG parameter array.
   */
  public static function formatCagKey($string) {
    // Make lowercase.
    $string = strtolower($string);
    // Replace spaces with underscores or else MPS will throw an error.
    $string = str_replace(' ', '_', $string);
    preg_replace("/[^0-9a-zA-Z_-\s\.]/", '', $string);
    return $string;
  }

  /**
   * Cleans a string for some reason.
   */
  public static function makeCleanString($string) {
    return preg_replace("/[^0-9a-zA-Z_-\s\.]/", '', strip_tags(urldecode(htmlspecialchars_decode(html_entity_decode($string), ENT_QUOTES))));
  }

  /**
   * Generates a unique identifier based on MPS site and path params.
   */
  public static function generateContentIdentifier($site, $path) {
    $generated_id = 'X' . (hexdec(substr(sha1($site . '|' . $path), 0, 15)) % 4294967295);
    return $generated_id;
  }
}
