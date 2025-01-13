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
    
    // $share_tab = $this->entity;

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
      ];
      $tab_form['entity']['id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('ID'),
        '#default_value' => $tab['entity']['id'],
        '#required' => TRUE,
      ];

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

      $form['tabs'][$key] = $tab_form;

      $tab_number++;

      $tab_options[$key] = $tab['title'];
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
      // '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];

    foreach ($tabs as $key => $tab) {
      $form['tab_order'][$key] = [
        '#attributes' => ['class' => ['draggable']],
        '#weight' => $tab['weight'] ?? 0,
        'label' => [
          '#markup' => $tab['title']
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $tab['title']]),
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
        'visible' => [
          ':input[name="share_method"]' => ['value' => ShareTab::SHARE_METHOD_HASH],
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
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  // public static function ajaxCallback(array $form, FormStateInterface $form_state) {
  //   // $button = $form_state->getTriggeringElement();
  //   return $form['tabs'];
  // }

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
    foreach ($tabs as $key => $tab) {
      $entity_storage = \Drupal::entityTypeManager()->getStorage($tab['entity']['type']);
      $entity = $entity_storage->load($tab['entity']['id']);
      if (!$entity) {
        $form_state->setErrorByName('tabs][' . $key . '][entity][id', $this->t('Entity does not exist!'));
      }
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
    // unset($values['manager_settings']['notifications']['add_more']);

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
}
