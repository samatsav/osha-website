<?php
/**
 * @file
 * Returns the HTML for an article node.
 */
?>

<article class="node-<?php print $node->nid; ?> <?php print $classes; ?> clearfix"<?php print $attributes; ?>>

  <?php if ($title_prefix || $title_suffix || $display_submitted || $unpublished || !$page && $title): ?>
    <header>
      <?php print render($title_prefix); ?>
      <?php if (!$page && $title): ?>
        <h2<?php print $title_attributes; ?>><a href="<?php print $node_url; ?>"><?php print $title; ?></a></h2>
      <?php endif; ?>
      <?php print render($title_suffix); ?>

      <?php if ($display_submitted): ?>
        <p class="submitted">
          <?php print $user_picture; ?>
          <?php print $submitted; ?>
        </p>
      <?php endif; ?>

      <?php if ($unpublished): ?>
        <mark class="unpublished"><?php print t('Unpublished'); ?></mark>
      <?php endif; ?>
    </header>
  <?php endif; ?>

  <?php
  // We hide the comments and links now so that we can render them later.
  hide($content['comments']);
  hide($content['links']);
  // unset to render below after a div
  if (isset($content['field_related_oshwiki_articles'])) {
    hide($content['field_related_oshwiki_articles']);
  }
  print render($content);
  if ( $view_mode == 'full' && $node->article_type_code == 'section' ) {
    if (!empty($tagged_wiki)) { ?>
      <div id="related-wiki">
        <div class="related_wiki_head"><span><?php print t('OSHWiki featured articles');?><span></div>
      <div>
    <?php
      foreach ($tagged_wiki as $wiki) {
        print render($wiki);
      }
    }
  }
  ?>

  <?php print render($content['links']); ?>

  <?php print render($content['comments']); ?>

</article>
