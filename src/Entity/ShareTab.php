<?php

namespace Drupal\share_tabs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

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
 *     "default_tab",
 *     "share_method",
 *     "empty_hash",
 *     "query_param_name",
 *   },
 *   cache = {
 *     "tags" = {"share_tab_list", "share_tab:{id}"}
 *   }
 * )
 */
class ShareTab extends ConfigEntityBase {
  const SHARE_METHOD_HASH = 'hash';
  const SHARE_METHOD_QUERY = 'query_param';
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

  /**
   * Tab details
   *
   * @var array
   */
  protected $tabs;

  /**
   * Default tab
   *
   * @var string
   */
  protected $default_tab;

  /**
   * Share method
   *
   * @var string
   */
  protected $share_method;
  
  /**
   * Empty hash
   *
   * @var boolean
   */
  protected $empty_hash = TRUE;

  /**
   * Query parameter name
   *
   * @var string
   */
  protected $query_param_name = 'stab';

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
        'view_mode' => NULL,
      ],
      'name' => [
        'autogenerate' => TRUE,
        'custom' => '',
      ],
      'ajax' => FALSE,
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

  public function getDefaultTab() {
    return $this->default_tab;
  }

  public function getShareMethod() {
    return $this->share_method;
  }

  public function getEmptyHash() {
    return $this->empty_hash;
  }

  public function getQueryParamterName() {
    return $this->query_param_name;
  }

  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach ($this->tabs as &$tab) {
      $tab['title'] = trim($tab['title']);
      $tab['name']['custom'] = trim($tab['name']['custom']);
    }
  }
}
