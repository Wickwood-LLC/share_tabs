<?php

namespace Drupal\share_tabs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the MyEntity configuration entity.
 *
 * @ConfigEntityType(
 *   id = "share_tab",
 *   label = @Translation("Share Tab"),
 *   handlers = {
 *     "list_builder" = "Drupal\share_tabs\ShareTabListBuilder",
 *     "form" = {
 *       "add" = "Drupal\share_tabs\Form\ShareTabForm",
 *       "edit" = "Drupal\share_tabs\Form\ShareTabForm",
 *       "delete" = "Drupal\share_tabs\Form\ShareTabDeleteForm"
 *     }
 *   },
 *   config_prefix = "share_tab",
 *   admin_permission = "administer share tab",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/share-tabs/add",
 *     "edit-form" = "/admin/structure/share-tabs/{share_tab}/edit",
 *     "delete-form" = "/admin/structure/share-tabs/{share_tab}/delete",
 *     "collection" = "/admin/structure/share-tabs"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tabs",
 *   }
 * )
 */
class ShareTab extends ConfigEntityBase {
  /**
   * The unique ID of the entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the entity.
   *
   * @var string
   */
  protected $label;

  protected $tabs;

  public function addTab() {
    if (!$this->tabs) {
      $this->tabs = [];
    }
    $this->tabs[time()] = [
      'title' => '',
      'weight' => 0,
      'entity' => [
        'type' => 'node',
        'id' => NULL,
      ]
    ];
  }

  public function removeTab($key) {
    if (isset($this->tabs[$key])) {
      unset($this->tabs[$key]);
    }
  }

  public function getTabs() {
    if (!$this->tabs) {
      $this->tabs = [];
    }
    return $this->tabs;
  }
}
