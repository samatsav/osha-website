<?php

class OSHATMGMTEntitySourcePluginController extends TMGMTEntitySourcePluginController {
  /**
   * Overwrite parent function to set the language from job item not job id.
   */
  public function saveTranslation(TMGMTJobItem $job_item) {
    $entity = entity_load_single($job_item->item_type, $job_item->item_id);
    $job = tmgmt_job_load($job_item->tjid);

    osha_tmgmt_field_populate_entity($job_item->item_type, $entity, $job_item->target_language, $job_item->getData());

    // Change the active language of the entity to the target language.
    $handler = entity_translation_get_handler($job_item->item_type, $entity);
    $handler->setFormLanguage($job_item->target_language);

    entity_save($job_item->item_type, $entity);

    $translation = array(
      // @todo Improve hardcoded values.
      'translate' => 0,
      'status' => TRUE,
      'language' => $job_item->target_language,
      'source' => $job_item->source_language,
    );

    $handler->setTranslation($translation);
    $handler->saveTranslations();
    $job_item->accepted();
  }

  public function getData(TMGMTJobItem $job_item) {
    if ($job_item->item_type == "node") {
      return self::getNodeData($job_item);
    }
    else {
      return parent::getData($job_item);
    }
  }

  public static function getNodeData(TMGMTJobItem $job_item) {
    $node = node_load($job_item->item_id);
    // Get all the fields that can be translated and arrange their values into
    // a specific structure.
    $data = tmgmt_field_get_source_data('node', $node, $job_item->getJob()->source_language);
    if (isset($node->metatags['en'])) {
      $meta = $node->metatags['en'];
      $data_metatags = array();
      if (isset($meta['title'])) {
        $data_metatags['#label'] = 'Meta Tags';
        $data_metatags['title'] = array(
          '#label' => 'DELTA #0',
          'value' => array(
            '#label' => 'Meta Tags Title',
            '#text' => $meta['title']['value'],
            '#translate' => TRUE,
          ),
        );
        $data_metatags['format'] = array(
          '#label' => '',
          '#text' => 'plain_text',
          '#translate' => FALSE,
        );
      }
      if (isset($meta['description'])) {
        $data_metatags['#label'] = 'Meta Tags';
        $data_metatags['description'] = array(
          '#label' => 'DELTA #0',
          'value' => array(
            '#label' => 'Meta Tags Description',
            '#text' => $meta['description']['value'],
            '#translate' => TRUE,
          ),
        );
        $data_metatags['format'] = array(
          '#label' => '',
          '#text' => 'plain_text',
          '#translate' => FALSE,
        );
      }
      if (isset($meta['abstract'])) {
        $data_metatags['#label'] = 'Meta Tags';
        $data_metatags['abstract'] = array(
          '#label' => 'DELTA #0',
          'value' => array(
            '#label' => 'Meta Tags abstract',
            '#text' => $meta['abstract']['value'],
            '#translate' => TRUE,
          ),
        );
        $data_metatags['format'] = array(
          '#label' => '',
          '#text' => 'plain_text',
          '#translate' => FALSE,
        );
      }
      if (!empty($data_metatags)) {
        $data['metatags'] = $data_metatags;
      }
    }
    return $data;
  }
}
