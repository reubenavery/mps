<?php

/**
 * @file
 * Admin settings pages for MPS
 */

/**
 * MPS administrative settings form.
 */
function mps_admin_settings() {
  $form['mps_base_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Base URL'),
    '#description' => t('Enter in form of a qualified domain, including a trailing slash.  Eg: http://mps.ivillage.com/'),
    '#default_value' => variable_get('mps_base_url', MPS_DEFAULT_BASE_URL),
    '#required' => TRUE,
  );

  $form['mps_exclude_paths'] = array(
    '#type' => 'textarea',
    '#title' => t('Pages to exclude from MPS processing'),
    '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
      '%blog' => 'blog',
      '%blog-wildcard' => 'blog/*',
      '%front' => '<front>',
    )),
    '#default_value' => variable_get('mps_exclude_paths', MPS_DEFAULT_EXCLUDE_PATHS),
  );

  $form['mps_mapping_defaults'] = array(
    '#type' => 'fieldset',
    '#title' => t('Mappings'),
    '#description' => t('Specify token rewrites.  Note that a call to hook_mps_request_alter() is made for each request, and this can be utilized for fine control on how these values are sent.'),
    '#tree' => TRUE,
  );

  // The only required fields: site and path.
  $form['mps_mapping_defaults']['site'] = array(
    '#type' => 'textfield',
    '#title' => 'Site ID [site]',
    '#description' => 'Example: ' . MPS_DEFAULT_SITE_ID,
    '#default_value' => mps_get_mapping_default('site', MPS_DEFAULT_SITE_ID),
    '#required' => TRUE,
  );
  $form['mps_mapping_defaults']['path'] = array(
    '#type' => 'textfield',
    '#title' => 'Path [path]',
    '#description' => t('Full path.  Must begin with a slash.  Do not include query parameters.'),
    '#default_value' => mps_get_mapping_default('path', '/[url:path]'),
    '#required' => TRUE,
  );

  // Taxonomy:
  $form['mps_mapping_defaults']['cat'] = array(
    '#type' => 'textfield',
    '#title' => 'Category [cat]',
    '#description' => t("Primary category within which content resides.  Example, 'entertainment'.  Multiple values are delimited by the '|' character."),
    '#default_value' => mps_get_mapping_default('cat'),
  );
  $form['mps_mapping_defaults']['pkgid'] = array(
    '#type' => 'textfield',
    '#title' => 'Package ID [pkgid]',
    '#description' => t('Deprecated:  The CMS defined package id of what this content belongs to.  Analogue of [cat].'),
    '#default_value' => mps_get_mapping_default('pkgid'),
  );

  // Content type:
  $form['mps_mapping_defaults']['content_id'] = array(
    '#type' => 'textfield',
    '#title' => 'Content ID [content_id]',
    '#description' => t('Numeric identifier.  For example, node ID.'),
    '#default_value' => mps_get_mapping_default('content_id'),
  );
  $form['mps_mapping_defaults']['type'] = array(
    '#type' => 'textfield',
    '#title' => 'Content type [type]',
    '#default_value' => mps_get_mapping_default('type'),
  );
  $form['mps_mapping_defaults']['subtype'] = array(
    '#type' => 'textfield',
    '#title' => 'Sub-content type [subtype]',
    '#description' => t('Can be utilized by Omniture.  Generally not needed.'),
    '#default_value' => mps_get_mapping_default('subtype'),
  );

  // Published date.
  $form['mps_mapping_defaults']['pubdate'] = array(
    '#type' => 'textfield',
    '#title' => 'Publish date [pubdate]',
    '#description' => t('The date the article was published in UNIX timestamp format.'),
    '#default_value' => mps_get_mapping_default('pubdate'),
  );

  // SEO fields.
  $form['mps_mapping_defaults']['title'] = array(
    '#type' => 'textfield',
    '#title' => 'Title [title]',
    '#default_value' => mps_get_mapping_default('title'),
  );
  $form['mps_mapping_defaults']['stitle'] = array(
    '#type' => 'textfield',
    '#title' => 'Short title [stitle]',
    '#default_value' => mps_get_mapping_default('stitle'),
  );
  $form['mps_mapping_defaults']['hline'] = array(
    '#type' => 'textfield',
    '#title' => 'Headline [hline]',
    '#default_value' => mps_get_mapping_default('hline'),
  );

  // Search results.
  $form['mps_mapping_defaults']['keywords'] = array(
    '#type' => 'textfield',
    '#title' => 'Keywords [keywords]',
    '#description' => t('If a page is the result of a search query, these are the terms used to reach it.'),
    '#default_value' => mps_get_mapping_default('keywords'),
  );

  // Parent object: used for items such as slideshow galleries.
  $form['mps_mapping_defaults']['envelope'] = array(
    '#type' => 'textfield',
    '#title' => 'Parent Object Title [envelope]',
    '#description' => t('The title of a container node or other object entity, for example "Christmas Gift Slideshow".'),
    '#default_value' => mps_get_mapping_default('envelope'),
  );
  $form['mps_mapping_defaults']['envelope_id'] = array(
    '#type' => 'textfield',
    '#title' => 'Parent Object ID [envelope_id]',
    '#description' => t('Numeric ID (node ID) of parent container.'),
    '#default_value' => mps_get_mapping_default('envelope_id'),
  );

  $form['mps_mapping_defaults']['partner'] = array(
    '#type' => 'textfield',
    '#title' => 'Partner [partner]',
    '#description' => t('Example, co-branded sites, referrals, partner information.'),
    '#default_value' => mps_get_mapping_default('partner'),
  );

  // TODO- implement field[field_name]=value
  $form['mps_mapping_defaults']['token'] = array(
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Replacement values'),
  );
  $form['mps_mapping_defaults']['token']['tokens'] = array(
    '#markup' => theme('token_tree', array(
      'token_types' => 'all',
      'click_insert' => TRUE,
      'show_restricted' => TRUE,
    )),
  );

  $form['mps_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Debug mode'),
    '#default_value' => variable_get('mps_debug', 0),
  );

  $form['#validate'][] = 'mps_admin_settings_validate';

  return system_settings_form($form);
}

/**
 * Setting validation callback.
 */
function mps_admin_settings_validate($form, &$form_state) {
  $form_state['values']['mps_mapping_defaults'] = array_filter($form_state['values']['mps_mapping_defaults']);
  cache_clear_all('mps_adunit_regions', 'cache');
  cache_clear_all('mps_component_regions', 'cache');
}
