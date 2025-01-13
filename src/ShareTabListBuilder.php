<?php

namespace Drupal\share_tabs;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a list controller for the ShareTab entity type.
 */
class ShareTabListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => t('Label'),
    ] + parent::buildHeader();
    return $header;
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }
}
