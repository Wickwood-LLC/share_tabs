<?php

namespace Drupal\share_tabs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\share_tabs\Entity\ShareTab;

/**
 * Form for adding and editing ShareTab entities.
 */
class ShareTabForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#attributes']['id'] = 'share-tab-form-wrapper';

    if (!$share_tab = $form_state->get('share_tab')) {
      $share_tab = $this->entity;
      $form_state->set('share_tab', $share_tab);
    }
    /** @var \Drupal\share_tabs\Entity\ShareTab $share_tab */

    $form_state->set('share_tab', $share_tab);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $share_tab->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $share_tab->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\share_tabs\Entity\ShareTab::load',
      ],
    ];

    $tabs_wrapper_id = 'tabs-wrapper';
    $form['tabs'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Tabs'),
      '#prefix' => '<div id="' . $tabs_wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $tab_number = 1;
    $tabs = $share_tab->getTabs();

    uksort($tabs, function($a, $b) use ($tabs) {
      $tabs[$a]['weight'] = $tabs[$a]['weight'] ?? 0;
      $tabs[$b]['weight'] = $tabs[$b]['weight'] ?? 0;
      if ($tabs[$a]['weight'] == $tabs[$b]['weight'] ) {
        return 0;
      }
      return ($tabs[$a]['weight'] < $tabs[$b]['weight']) ? -1 : 1;
    });

    $content_entity_types = $this->getContentEntityTypes();
    $tab_options = [];

    foreach ($tabs as $key => $tab) {
      $tab_form = [
        '#type' => 'fieldset',
        '#title' => $this->t('Tab #@tab_number', ['@tab_number' => $tab_number]),
      ];
      $tab_form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $tab['title'],
        '#required' => TRUE,
      ];

      $tab_form['name'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Name'),
      ];
      $tab_form['name']['autogenerate'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Autogenerate'),
        '#default_value' => $tab['name']['autogenerate'],
        '#description' => $this->t('Autogenerate a name for this tab using the title set above. Name will be used to identify the tab in URLs.'),
      ];

      $tab_form['name']['custom'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom Name'),
        '#default_value' => $tab['name']['custom'],
        '#description' => $this->t('Manually set a custom name. Only alphanumeric characters and hyphens are allowed.'),
        '#states' => [
          'invisible' => [
            ':input[name="tabs[' . $key . '][name][autogenerate]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $tab_form['entity'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Entity'),
      ];

      $tab_form['entity']['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => $content_entity_types,
        '#default_value' => $tab['entity']['type'],
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::addTabCallback',
          'wrapper' => $form['#attributes']['id'],
          // 'effect' => 'fade',
        ],
      ];
      $tab_form['entity']['id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('ID'),
        '#default_value' => $tab['entity']['id'],
        '#required' => TRUE,
      ];
      $tab_form['entity']['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View Mode'),
        '#options' => static::getViewModeOptions($tab['entity']['type']),
        '#default_value' => $tab['entity']['view_mode'] ?? '',
        '#required' => TRUE,
      ];

      $tab_form['ajax'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Load by ajax'),
        '#default_value' => $tab['ajax'],
        '#description' => $this->t('The tab content will not be loaded until becoming the active tab.'),
      ];

      if (count($tabs) > 1) {
        $tab_form['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_role_' . $key,
          '#limit_validation_errors' => [['tabs',]],
          '#submit' => [[static::class, 'removeTabSubmit']],
          '#ajax' => [
            'callback' => '::removeTabCallback',
            'wrapper' => $form['#attributes']['id'],
            'effect' => 'fade',
          ],
        ];
      }

      $form['tabs'][$key] = $tab_form;

      $tab_number++;

      $tab_options[$key] = empty($tab['title'])? $tab_form['#title'] : $tab['title'];
    }

    if (empty($tabs)) {
      $form['tabs']['message'] = [
        '#markup' => $this->t('Start adding tabs using the "Add Tab" button given below.'),
      ];
    }

    $form['add_tab'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Tab'),
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::addTabCallback', // AJAX callback method.
        'wrapper' => $form['#attributes']['id'],
        'event' => 'click', // The event triggering the AJAX request.
      ],
      '#submit' => [[static::class, 'addTabSubmit']],
    ];

    if (!empty($tabs)) {
      $group_class = 'tab-order';
      $form['tab_order'] = [
        '#type' => 'table',
        '#caption' => $this->t('Rearrange tabs below to make them get displayed in the order you want.'),
        '#attributes' => ['id' => 'tab-order'],
        '#title' => $this->t('Tab Display Order'),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => $group_class,
          ],
        ],
        '#input' => FALSE,
        '#theme_wrappers' => ['form_element'],
      ];

      foreach ($tabs as $key => $tab) {
        $form['tab_order'][$key] = [
          '#attributes' => ['class' => ['draggable']],
          '#weight' => $tab['weight'] ?? 0,
          'label' => [
            '#markup' => $tab_options[$key]
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $tab_options[$key]]),
            '#title_display' => 'invisible',
            '#default_value' => $tab['weight'] ?? 0,
            '#attributes' => [
              'class' => [
                $group_class,
              ]
            ],
          ]
        ];
      }

      $form['default_tab'] = [
        '#type' => 'radios',
        '#title' => $this->t('Default Tab'),
        '#options' => $tab_options,
        '#default_value' => $share_tab->getDefaultTab(),
        '#description' => $this->t('Select a tab that to be open by default.'),
      ];
    }

    $form['share_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Share Method'),
      '#options' => [
        ShareTab::SHARE_METHOD_HASH => 'Hash',
        ShareTab::SHARE_METHOD_QUERY => 'Query Parameter',
      ],
      '#default_value' => $share_tab->getShareMethod(),
      '#description' => $this->t('Select a way to set the share method. This decides how infomation about the active tab appear in the URL. `Hash` will causet to make URL like [url]+#[tab-key].  `Query Param` will add a URL query parameter.'),
    ];

    $form['empty_hash'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow tabs to have empty hash'),
      '#default_value' => $share_tab->getEmptyHash(),
      '#description' => $this->t('Enabling this will leave tab anchor elements to have just `#` as `href` attribute value, which allows to make the anchor tags as links otherwise it will make it like simple span like tag.'),
      '#states' => [
        'invisible' => [
          ':input[name="share_method"]' => ['value' => ShareTab::SHARE_METHOD_HASH],
        ],
      ],
    ];

    $form['query_param_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query Parameter Name'),
      '#default_value' => $share_tab->getQueryParamterName(),
      '#description' => $this->t('Parameter name to be used for the query. It will be useful to avoid any conflict with any other possible query paramter, change this in that case. Otherwise leaeve as it is.'),
      '#states' => [
        'visible' => [
          ':input[name="share_method"]' => ['value' => ShareTab::SHARE_METHOD_QUERY],
        ],
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback method.
   */
  public function removeTabCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX callback method.
   */
  public function addTabCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Submission handler for the "Add Tab" button.
   */
  public static function addTabSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    /** @var \Drupal\share_tabs\Entity\ShareTab */
    $share_tab = $form_state->get('share_tab');
    $share_tab->addTab();
    $form_state->set('share_tab', $share_tab);

    $form_state->setRebuild();
  }

  /**
   * Submission handler for the "Add Tab" button.
   */
  public static function removeTabSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    end($button['#parents']);
    $tab_to_remove = prev($button['#parents']);

    /** @var \Drupal\share_tabs\Entity\ShareTab */
    $share_tab = $form_state->get('share_tab');
    $share_tab->removeTab($tab_to_remove);
    $form_state->set('share_tab', $share_tab);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tabs = $form_state->getValue('tabs');
    if (empty($tabs)) {
      $form_state->setErrorByName('tabs', $this->t('At least one tab is required.'));
    }
    else {
      foreach ($tabs as $key => $tab) {
        $entity_storage = \Drupal::entityTypeManager()->getStorage($tab['entity']['type']);
        $entity = $entity_storage->load($tab['entity']['id']);
        if (!$entity) {
          $form_state->setErrorByName('tabs][' . $key . '][entity][id', $this->t('Entity does not exist!'));
        }
        if (!$tab['name']['autogenerate']) {
          $custom_name = trim($tab['name']['custom']);
          if (empty(trim($custom_name))) {
            $form_state->setErrorByName('tabs][' . $key . '][name][custom', $this->t('Cannot be empty!'));
          }
          else if (!preg_match('/^[\w\-]+$/', $custom_name)) {
            $form_state->setErrorByName('tabs][' . $key . '][name][custom', $this->t('Only alphanumeric characters are allowed!'));
          }
        }
      }
    }
    if (!preg_match('/^[\w\-]+$/', $form_state->getValue('query_param_name'))) {
      $form_state->setErrorByName('query_param_name', $this->t('Only alphanumeric charactes and hyphens are allowed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label entity.', ['%label' => $entity->label()]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label entity was not saved.', ['%label' => $entity->label()]), 'error');
    }

    $form_state->setRedirect('entity.share_tab.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values['tab_order'] ?? [] as $key => $order_data) {
      $values['tabs'][$key]['weight'] = $order_data['weight'];
    }

    unset(
      $values['tab_order'],
    );

    $form_state->setValues($values);
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * Get a list of content entity types.
   * @return array
   *   An array of content entity type definitions keyed by their machine name.
   */
  public function getContentEntityTypes() {
    $content_entity_types = [];

    // Get all entity type definitions.
    $entity_definitions = \Drupal::entityTypeManager()->getDefinitions();

    // Filter content entity types.
    foreach ($entity_definitions as $entity_type_id => $entity_definition) {
      if ($entity_definition instanceof EntityTypeInterface && $entity_definition->getGroup() === 'content') {
        $content_entity_types[$entity_type_id] = $entity_definition->getLabel();
      }
    }

    return $content_entity_types;
  }

  /**
   * Get a list of view modes for a specific entity type.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'user', 'taxonomy_term').
   *
   * @return array
   *   An array of view modes with machine names as keys and labels as values.
   */
  public static function getViewModeOptions(string $entity_type) {
    static $view_modes = [];
    if (!isset($view_modes[$entity_type])) {
      // Get the entity display repository service.
      $entity_display_repository = \Drupal::service('entity_display.repository');

      // Retrieve all view modes for the entity type.
      $entity_view_modes = $entity_display_repository->getViewModes($entity_type);

      // Extract machine names and labels.
      $view_modes_list = [];
      foreach ($entity_view_modes as $machine_name => $view_mode) {
        $view_modes_list[$machine_name] = $view_mode['label'];
      }
      $view_modes[$entity_type] = $view_modes_list;
    }

    return $view_modes[$entity_type];
  }
}
