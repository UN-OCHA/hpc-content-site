<?php

namespace Drupal\gho_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Implementation of the GhoDownloadController class.
 */
class GhoDownloadController extends ControllerBase {

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
   * DownloadController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(RequestStack $request_stack, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->requestStack = $request_stack;
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager')
    );
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
