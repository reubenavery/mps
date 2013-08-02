# MPS Integration Module #

Written by Reuben Avery <reuben.avery@nbcuni.com>

## Requirements ##
  1. Drupal 7
  2. MPS 3

## Installation Instructions ##
  1. Download this module and place in your sites/all/modules directory.
  2. If you don't already have them, this module depends on the token module, and entity_token module is highly recommended.
  2. Enable via Drupal.
  3. Navigate to admin/config/services/mps to configure the mappings.  Use tokens!
  4. Configure placement of MPS adunit and component blocks in admin/structure/blocks, in admin/structure/panels, or however your site is setup.
  
## API Notes ##

There are two hook implementations you may use to extend and tweak this module's behavior:

    function hook_mps_request_alter(&$request, $mps) {
      // Add the following value to the MPS request URL:
      $request['data']['foo'] = 'bar';
    }
    
And:

    function hook_mps_response_alter(&$response, $mps) {
      // Remove metadata items from response as these are 
      // handled by the meta module:
      unset($response['data']['pagevars']['meta_description']);
      unset($response['data']['pagevars']['meta_keywords']);
    }
    