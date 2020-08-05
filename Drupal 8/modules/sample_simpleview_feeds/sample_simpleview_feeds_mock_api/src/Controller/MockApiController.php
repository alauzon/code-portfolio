<?php

namespace Drupal\sample_simpleview_feeds_mock_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * The MockApi controller.
 */
class MockApiController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content($feedName) {

    $response = new Response();
    $response->headers->set('Content-Type', 'text/xml');
    $content = file_get_contents(DRUPAL_ROOT . '/' . drupal_get_path('module', 'sample_simpleview_feeds_mock_api') . '/files/' . $feedName );
    $response->setContent($content);

    return $response;
  }

}
