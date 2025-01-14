<?php

namespace Drupal\share_tabs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class ShareTabsController extends ControllerBase {

  /**
   * Responds to the AJAX request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing data.
   */
  public function tabContent(Request $request) {
    // Get data from the POST request.
    $data = $request->request->all();

    $entity_storage = \Drupal::entityTypeManager()->getStorage($data['entity']['type']);
    $entity = $entity_storage->load($data['entity']['id']);
    $view_mode = !empty($data['entity']['view_mode']) ? $data['entity']['view_mode'] : 'full';
    $selector = '#' . $data['tab_content_id'];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand( $selector, \Drupal::entityTypeManager()
      ->getViewBuilder($data['entity']['type'])
      ->view($entity, $view_mode)));
    $response->addCommand(new InvokeCommand($selector, 'attr', ['data-ajax-loaded', '1']));
    return $response;
  }

}
