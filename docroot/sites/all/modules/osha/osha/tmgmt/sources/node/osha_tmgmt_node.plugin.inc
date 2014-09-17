<?php

class OSHATMGMTNodeSourcePluginController extends TMGMTNodeSourcePluginController {

  /**
   * {@inheritdoc}
   */
  public function getData(TMGMTJobItem $job_item) {
    return OSHATMGMTEntitySourcePluginController::getNodeData($job_item);
  }
}
