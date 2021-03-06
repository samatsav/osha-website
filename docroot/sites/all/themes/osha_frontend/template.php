<?php
/**
 * @file
 * Osha frontend template functionality
 */

/**
 * Implements theme_links__system_main_menu().
 */
function osha_frontend_links__system_main_menu() {
  return NULL;
}

/**
 * Implements theme_menu_tree__main_menu().
 */
function osha_frontend_menu_tree__main_menu($variables) {
  return '<ul id="main-menu-links" class="menu clearfix">'
    . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_menu_tree__search_menu().
 *
 * Add classes to search menu to style like tabs.
 */
function osha_frontend_menu_tree__menu_search($variables) {
  return '<ul id="search_menu_tabs" class="tabs-primary tabs primary">'
  . $variables['tree'] . '</ul>';
}

/**
 * Implements hook_menu_link__menu_search().
 *
 * Add classes to search menu to style like tabs.
 */
function osha_frontend_menu_link__menu_search($variables) {
  $element = $variables['element'];
  $active = '';
  if (in_array('active-trail', $element['#attributes']['class'])) {
    $active = 'is-active active';
  }
  $element['#attributes']['class'] = array(
    'tabs-primary__tab', $active,
  );
  $element['#localized_options']['attributes']['class'] = array(
    'tabs-primary__tab-link', $active,
  );
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . "</li>\n";
}

/**
 * Implements theme_menu_link__menu_block().
 */
function osha_frontend_menu_link__menu_block($variables) {
  $element = &$variables['element'];
  $delta = $element['#bid']['delta'];

  // Get the variable provided by osha_menu module.
  $render_img = variable_get('menu_block_' . $delta . '_' . OSHA_MENU_RENDER_IMG_VAR_NAME, 0);
  if (!$render_img) {
    return theme_menu_link($variables);
  }
  // $element['#attributes']['data-image-url'] = $image_url;
  $output_link = l($element['#title'], $element['#href'], $element['#localized_options']);

  $output_image = "";
  if (!empty($element['#localized_options']['content']['image'])
      && $image_url = file_create_url($element['#localized_options']['content']['image'])) {
    $image = '<img src="' . $image_url . '"/>';
    $output_image = l($image, $element['#href'], array('html' => TRUE));
  }

  return '<li' . drupal_attributes($element['#attributes']) . '>
    <div class="introduction-title">' . $output_link . '</div>
    <div class="introduction-image">' . $output_image . '</div>
    </li>';
}

/**
 * Implements hook_theme().
 */
function osha_frontend_date_display_single(&$variables) {
  $date_theme = '';
  if (!empty($variables['dates']['value']['osha_date_theme'])) {
    $date_theme = $variables['dates']['value']['osha_date_theme'];
  }
  switch ($date_theme) {
    case 'calendar':
      return osha_frontend_date_calendar_icon($variables);

    default:
      return theme_date_display_single($variables);
  }
}

/**
 * Split the date into spans to be formatted as calendar icon.
 */
function osha_frontend_date_calendar_icon($variables) {
  $time = strtotime($variables['date']);
  $month = date('m', $time);
  $day = date('d', $time);
  return
    '<div class="osha-date-calendar">
      <span class="osha-date-calendar-month">' . $month . '</span>
      <span class="osha-date-calendar-day">' . $day . '</span>
    </div>';
}

/**
 * Implements hook_apachesolr_sort_list().
 */
function osha_frontend_apachesolr_sort_list($vars) {
  $items = &$vars['items'];
  unset($items['sort_label']);
  unset($items['bundle']);
  unset($items['sort_name']);
  return theme('item_list', array('items' => $vars['items']));
}



/**
 * Called from hook_preprocess_node
 */
function fill_related_publications(&$vars) {
  $vars['total_related_publications'] = 0;
  // get 3 related publications by common tags
  $tags_tids = array();
  if (!empty($vars['field_tags'])) {
    $tags_tids = $vars['field_tags'][LANGUAGE_NONE];
  }

  if (!empty($tags_tids)) {
    // query all publications with the same tags
    $tids = array();
    foreach ($tags_tids as $tid) {
      array_push($tids, $tid['tid']);
    }

    $query = new EntityFieldQuery();
    // exclude self
    $excluded_nids = array();
    array_push($excluded_nids, $vars['node']->nid);

    $result = $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'publication')
      ->entityCondition('entity_id', $excluded_nids, 'NOT IN')
      ->fieldCondition('field_tags', 'tid', $tids, 'IN')
      ->propertyOrderBy('changed', 'DESC')
      ->pager(3)
      ->execute();

    if (!empty($result)) {
      $vars['total_related_publications'] = sizeof($result['node']);
      $vars['tagged_related_publications'] = array();
      foreach ($result['node'] as $n) {
        $node = node_load($n->nid);
        $vars['tagged_related_publications'][] = node_view($node,'teaser');
      }
    }
  }
}

/**
 * Called from hook_preprocess_node
 */
function fill_related_wiki(&$vars) {
  $wiki_articles_no = 0;
  $vars['tagged_wiki'] = array();
  if (!empty($vars['field_related_oshwiki_articles'])) {
    $manual_wiki_articles = $vars['field_related_oshwiki_articles'][LANGUAGE_NONE];
    $wiki_articles_no = sizeof($manual_wiki_articles);
    // add manually tagged wiki articles (hidden in display mode)
    foreach ($manual_wiki_articles as $related_wiki) {
      $node = node_load($related_wiki['target_id']);
      $vars['tagged_wiki'][] = node_view($node,'osha_wiki');
    }
  }

  if ($wiki_articles_no < 2) {
    $limit = 2 - $wiki_articles_no;
    // get 2-$wiki_articles_no tagged wiki
    $wiki_categories_tids = array();
    if (!empty($vars['field_wiki_categories'])) {
      $wiki_categories_tids = $vars['field_wiki_categories'][LANGUAGE_NONE];
    }

    if (!empty($wiki_categories_tids)) {
      // query all wiki articles in the same category or its children
      $tids = array();
      $voc = taxonomy_vocabulary_machine_name_load('wiki_categories');
      foreach ($wiki_categories_tids as $tid) {
        // normally only one $tid, but just in case
        array_push($tids, $tid['tid']);
        // load and push also children
        $terms = taxonomy_get_tree($voc->vid, $tid['tid']);
        foreach ($terms as $term) {
          array_push($tids, $term->tid);
        }
      }

      // exclude manually related
      $excluded_nids = array();
      array_push($excluded_nids, 0); // avoid empty NOT IN clause
      if (!empty($vars['field_related_oshwiki_articles'])) {
        foreach ($vars['field_related_oshwiki_articles'] as $related_wiki) {
          array_push($excluded_nids, $related_wiki[0]['target_id']);
        }
      }
      $query = new EntityFieldQuery();
      $result = $query->entityCondition('entity_type', 'node')
        ->entityCondition('bundle', 'wiki_page')
        ->entityCondition('entity_id', $excluded_nids, 'NOT IN')
        ->fieldCondition('field_wiki_categories', 'tid', $tids, 'IN')
        ->fieldOrderBy('field_updated', 'value', 'DESC')
        ->pager($limit)
        ->execute();
      if (!empty($result)) {
        foreach ($result['node'] as $n) {
          $node = node_load($n->nid);
          $vars['tagged_wiki'][] = node_view($node,'osha_wiki');
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function osha_frontend_process_node(&$vars) {
  // Change default text of the read more link.
  if ($vars['type'] != 'press_release' && $vars['view_mode'] == 'full') {
    if (isset($vars['content']['links']['print_pdf'])) {
      // only press release could be downloaded as pdf
      unset($vars['content']['links']['print_pdf']);
    }
  }
  if ($vars['type'] == 'publication' && $vars['view_mode'] == 'full' ) {
    fill_related_publications($vars);
  }
  if ($vars['type'] == 'article' || $vars['type'] == 'publication' && $vars['view_mode'] == 'full') {
    fill_related_wiki($vars);
  }
  if (isset($vars['content']['links']['node']['#links']['node-readmore'])) {
    $vars['content']['links']['node']['#links']['node-readmore']['title'] = t('Show details');
  }

  /*insert views blocks - disabled for the moment
  add_blocks_inside_content($vars);*/
}

/**
 * Called from hook_preprocess_node()
 * Insert view or custom blocks in node when meet a specific markup
 * The markup is like <!--[name-of-the-block]-->
 */
function add_blocks_inside_content(&$vars){
  $body = $vars['content']['body'][0]['#markup'];
  $pattern = '/(<!--\[)([(\w+)(\-+)(\_+)(\d+)]+)(\]-->)/';

  if(preg_match_all($pattern, $body, $matches)){
    $blocks = $matches[2];

    foreach($blocks as $block){
      //try load a view block
      $block_object = block_load('views', $block);
      //load a custom block
      if(!isset($block_object->bid))
        $block_object = block_load('block', $block);

      if(isset($block_object->bid)){
        $render_array =  _block_get_renderable_array(_block_render_blocks(array($block_object)));
        $body = str_replace('<!--['.$block.']-->', render($render_array), $body);
      }
    }
    $vars['content']['body'][0]['#markup'] = $body;
  }
}

/**
 * Implements hook_form_alter().
 */
function osha_frontend_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'search_block_form') {
    $form['search_block_form']['#attributes']['placeholder'] = t('Search');
  }
}

/**
 * Implements hook_preprocess_HOOK() for theme_file_icon().
 *
 * Change the icon directory to use icons from this theme.
 */
function osha_frontend_preprocess_file_icon(&$variables) {
  $variables['icon_directory'] = drupal_get_path('theme', 'osha_frontend') . '/images/file_icons';
}

/**
 * Implements theme_on_the_web_image().
 *
 * @param $variables
 *   An associative array with generated variables.
 *
 * @return
 *   HTML for a social media icon.
 */
function osha_frontend_on_the_web_image($variables) {
  $service = $variables['service'];
  $title   = $variables['title'];
  $size    = variable_get('on_the_web_size', 'sm');

  $variables = array(
    'alt'   => $title,
    'path'  => drupal_get_path('theme', 'osha_frontend') . '/images/social-icons/' . $size . '/' . $service . '.png',
    'title' => $title
  );

  return theme('image', $variables);
}


/**
 * Returns HTML for an individual feed item for display in the block.
 *
 * @param $variables
 *   An associative array containing:
 *   - item: The item to be displayed.
 *   - feed: Not used.
 *
 * @ingroup themeable
 */
function osha_frontend_aggregator_block_item($variables) {
  // Display the external link to the item.
  $item = $variables['item'];

  $element = '<span class="feed-item-date">'. format_date($item->timestamp, 'custom', variable_get('date_format_osha_day_only', 'd/m/Y')) .'</span>';
  $element .= '<br/>';
  $element .= '<a href="' . check_url($item->link) . '">' . check_plain($variables['item']->title) . "</a>\n";
  return $element;
}

/**
 * @todo @Ivan: Edit only below
 */





/**
 * @todo @Ivan: Do not go below this line
 */
