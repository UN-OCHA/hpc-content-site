<?php

namespace Drupal\gho_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Implementation of the GhoDownloadController class.
 */
final class GhoDownloadController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->requestStack = $container->get('request_stack');
    $instance->fileSystem = $container->get('file_system');
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    return $instance;
  }

  /**
   * Serves the file upon request.
   *
   * For better UX, we throw a page not found instead of a 403 but we make sure
   * the response can be invalidated when the node, permissions etc. change.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A valid node object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Serve the file as the response.
   *
   * @todo throw a page not found for English links?
   *
   * @throws \Drupal\Core\Http\Exception\CacheableNotFoundHttpException
   */
  public function download(NodeInterface $node) {
    // Check that the node is an article and has the proper field.
    if ($node->bundle() !== 'article' || !$node->hasField('field_pdf')) {
      throw new CacheableNotFoundHttpException($node);
    }

    // Load the file.
    $fid = $node->field_pdf->target_id;
    if (empty($fid)) {
      throw new CacheableNotFoundHttpException($node);
    }
    $file = $this->entityTypeManager()->getStorage('file')->load($fid);
    if (empty($file)) {
      throw new CacheableNotFoundHttpException($node);
    }

    // Check if the file exists.
    $uri = $file->getFileUri();
    $filename = $file->getFilename();
    $scheme = $this->streamWrapperManager->getScheme($uri);
    if (!$this->streamWrapperManager->isValidScheme($scheme) || !file_exists($uri)) {
      throw new CacheableNotFoundHttpException($node);
    }

    // Let other modules provide headers and controls access to the file.
    $headers = $this->moduleHandler()->invokeAll('file_download', [$uri]);
    if (empty($headers) || in_array(-1, $headers)) {
      throw new CacheableNotFoundHttpException($node);
    }

    // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    // sets response as not cacheable if the Cache-Control header is not
    // already modified. We pass in FALSE for non-private schemes for the
    // $public parameter to make sure we don't change the headers.
    $response = new BinaryFileResponse($uri, Response::HTTP_OK, $headers, $scheme !== 'private');
    if (empty($headers['Content-Disposition'])) {
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_INLINE,
        $filename
      );
    }

    return $response;
  }

}
