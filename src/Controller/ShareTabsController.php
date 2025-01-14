<?php

namespace Drupal\share_tabs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ShareTabsController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ShareTabsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to the AJAX request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The AJAX response containing the rendered entity content.
   */
  public function tabContent(Request $request): Response {
    // Get data from the POST request.
    $data = $request->request->all();

    // Validate the required data.
    if (empty($data['entity']['type']) || empty($data['entity']['id']) || empty($data['tab_content_id'])) {
      return new JsonResponse(['error' => 'Invalid request data.'], Response::HTTP_BAD_REQUEST);
    }

    // Load the entity.
    $entity_storage = $this->entityTypeManager()->getStorage($data['entity']['type']);
    $entity = $entity_storage->load($data['entity']['id']);

    if (!$entity) {
      return new JsonResponse(['error' => 'Entity not found.'], Response::HTTP_NOT_FOUND);
    }

    $view_mode = !empty($data['entity']['view_mode']) ? $data['entity']['view_mode'] : 'full';
    $selector = '#' . $data['tab_content_id'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand( $selector, $this->entityTypeManager()
      ->getViewBuilder($data['entity']['type'])
      ->view($entity, $view_mode)));
    $response->addCommand(new InvokeCommand($selector, 'attr', ['data-ajax-loaded', '1']));
    $response->addCommand(new InvokeCommand($selector, 'attr', ['aria-busy', 'false']));
    return $response;
  }

}
