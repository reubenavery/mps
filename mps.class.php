<?php

/*
 * @file Master Page Service manager class.
 */

class MPS {
  protected $mps_base_url;

  function __construct() {
    $this->mps_base_url = variable_get('mps_base_url', MPS_DEFAULT_BASE_URL);

    return $this;
  }

  /**
   * Constructs the request.
   */
  function buildRequest($params) {
    global $user;

    $request = array(
      'url' => $this->mps_base_url . MPS_DEFAULT_JSON_API_PATH,
      'data' => array(),
    );

    $path = $params['path'] ? drupal_get_normal_path($params['path']) : variable_get('site_frontpage', 'node');
    $menu_item = menu_get_item($path);
    $object = $menu_item['page_arguments'][0];

    $defaults = variable_get('mps_mapping_defaults', array());

    // Loop through the mapping defaults and build up the request parameter
    // array.  Pass each default through the token filter, providing a node
    // or taxonomy object (depending) and a user object.
    foreach ($defaults as $key => $value) {
      $data =  array('user' => $user);
      if (isset($object->nid)) {
        $data['node'] = $object;
      }
      else if (isset($object->tid)) {
        $data['term'] = $object;
      }
      if ($replaced = token_replace($value, $data, array('clear' => TRUE))) {
        $request['data'][$key] = $replaced;
      }
    }

    return $request;
  }

  /**
   * Executes request to MPS
   */
  function executeRequest($request) {
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
    return drupal_clean_css_identifier($identifier, $filter = array(' ' => '_', '_' => '_', '/' => '_', '[' => '', ']' => ''));
  }

  /**
   * Retrieves from MPS a description of its API and available items.
   */
  protected function getServiceDescription() {
    $description = &drupal_static(__FUNCTION__);

    if (empty($description)) {
      $url = $this->mps_base_url . variable_get('mps_api_descriptor', MPS_DEFAULT_API_DESCRIPTOR) . '/'. mps_get_mapping_default('site', MPS_DEFAULT_SITE_ID);
      if (!valid_url($url, TRUE)) {
        watchdog('MPS', 'Bad service URL: %url', array('%url', $url), WATCHDOG_ERROR);
        if ($this->inDebugMode()) {
          drupal_set_message('MPS Bad descriptor url: '. $url, 'error');
        }
      }
      if ($this->inDebugMode()) {
        drupal_set_message('MPS Descriptor call to '. $url);
      }
      $response = drupal_http_request($url);
      if (isset($response->data)) {
       $description = drupal_json_decode($response->data);
      }
      else {
        watchdog('MPS', 'Couldnt get site description from MPS', array(), WATCHDOG_ERROR);
        if ($this->inDebugMode()) {
          drupal_set_message('MPS didnt return any data from its descriptor service.', 'error');
        }
      }
    }

    return $description;
  }

  /**
   * Checks to see if a page path has been designated as excluded from MPS
   * processing.
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

  //
  // From Rich Rhee via http://pastebin.com/Fp2fuPbm:
  //

  public static function formatCagKey($string) {
    # Make lowercase
    $string = strtolower($string);
    # Replace spaces with underscores or else MPS will throw an error
    $string = str_replace(' ','_',$string);
    preg_replace("/[^0-9a-zA-Z_-\s\.]/", '', $string);
    return $string;
  }

  public static function makeCleanString($string) {
    return preg_replace("/[^0-9a-zA-Z_-\s\.]/", '', strip_tags(urldecode(htmlspecialchars_decode(html_entity_decode($string),ENT_QUOTES))));
  }

  public static function generateContentIdentifier($site,$path) {
    $generated_id = 'X'.(hexdec(substr(sha1($site.'|'.$path), 0, 15)) % 4294967295);
    return $generated_id;
  }
}