<?php

namespace Drupal\share_tabs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\share_tabs\Entity\ShareTab;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an inline block plugin type.
 *
 * @Block(
 *  id = "share_tab_block",
 *  admin_label = @Translation("Share Tab block"),
 *  category = @Translation("Share Tab blocks"),
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ShareTabBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Share Tabs entity associated with this block
   *
   * @var \Drupal\share_tabs\Entity\ShareTab
   */
  protected $share_tab;


  /**
   * Constructs a new InlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'share_tab' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $share_tab_storage = $this->entityTypeManager->getStorage('share_tab');
    $share_tab_options = [];
    foreach ($share_tab_storage->loadMultiple() as $share_tab) {
      $share_tab_options[$share_tab->id()] = $share_tab->label();
    }

    $form['share_tab'] = [
      '#type' => 'select',
      '#options' => $share_tab_options,
      '#title' => $this->t('Share Tab'),
      '#description' => $this->t('The Share Tab to display.'),
      '#default_value' => $this->configuration['share_tab'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['share_tab'] = $form_state->getValue('share_tab');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($share_tab = $this->getShareTab()) {
      return [
        '#theme' => 'share_tabs',
        '#share_tab' => $share_tab,
        '#attached' => [
          'library' => [
            'share_tabs/share_tabs',
          ],
        ],
      ];
    }
    return [];
  }

  /**
   * Get share tab config entity associated with this block.
   */
  public function getShareTab(): ShareTab {
    if (!$this->share_tab) {
    $this->share_tab = $this->entityTypeManager->getStorage('share_tab')->load($this->configuration['share_tab']);
    }
    return $this->share_tab;
  }

  /**
   * @inheritdoc
   */
  public function getCacheTags()  {
    $tags = parent::getCacheTags();
    if ($share_tab = $this->getShareTab()) {
      $tags += $share_tab->getCacheTags();
      foreach ($share_tab->getTabs() as $tab) {
        $tags[] = $tab['entity']['type'] . ':' . $tab['entity']['id'];
      }
    }

    return $tags;
  }

  /**
   * @inheritdoc
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    if ($share_tab = $this->getShareTab()) {
      if ($share_tab->getShareMethod() == ShareTab::SHARE_METHOD_QUERY) {
        $contexts[] = 'url.query_args:' . $share_tab->getQueryParamterName();
      }
    }
    return $contexts;
  }

  /**
   * @inheritdoc
   */
  public function getCacheMaxAge() {
    return CacheBackendInterface::CACHE_PERMANENT;
  }
}
