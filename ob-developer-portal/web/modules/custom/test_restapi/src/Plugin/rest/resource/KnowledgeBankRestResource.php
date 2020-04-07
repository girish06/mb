<?php

namespace Drupal\test_restapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\block_content\Entity\BlockContent;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "knowledge_bank_apis",
 *   label = @Translation("Get rest resource for Knowledge Bank"),
 *   uri_paths = {
 *     "canonical" = "/knowledge-bank-apis"
 *   }
 * )
 */
class KnowledgeBankRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('example_rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $top_banner = array();
    $node = Node::load(22);
    $top_banner['title'] = $node->get('title')->value;
    $top_banner['body'] = $node->get('body')->value;
    $image = $node->get('field_image')->entity->uri->value;
    if (!empty($image)) {
      $image = file_create_url($image);
      $top_banner['image'] = $image;
    }

    $paragraph = $node->field_faq->getValue();
    // Loop through the result set.
    $key = 0;
    foreach ( $paragraph as $element ) {
        $p = Paragraph::load( $element['target_id'] );
        if(!empty($faq)) {
            if(!isset($faq[$key]['faq_type']) || $p->field_faq_type->getValue()[0]['value'] != $faq[$key]['faq_type']) {
                $key++;
                $faq[$key]['faq_type'] = $p->field_faq_type->getValue()[0]['value'];
            }
        }else{
            $faq[$key]['faq_type'] = $p->field_faq_type->getValue()[0]['value'];
        }
        $faq[$key]['faq_content'][] = array(
          'faq_question' => $p->field_faq_question->getValue()[0]['value'],
          'faq_answer' => $p->field_faq_answers->getValue()[0]['value']
        );
    }
    
    $block = BlockContent::load(15);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $footer_banner['title'] = $block_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $footer_banner['body'] = $block_body;
      }
      $block_image = $block->get('field_basic_block_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $footer_banner['image'] = $image;
      }    
    }

    $output = array(
        'top_banner' => $top_banner,
        'faq'  =>  $faq,
        'footer_banner' => $footer_banner
    );
   
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }

}