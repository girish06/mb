<?php

namespace Drupal\test_restapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\apigee_edge\Entity\Controller\DeveloperAppCredentialController;
use Drupal\Core\Config\ConfigFactory;

//use Apigee\Edge\Api\Management\Serializer\AppCredentialSerializer;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "my_apps_list",
 *   label = @Translation("Get the list of apps for specific user"),
 *   uri_paths = {
 *     "canonical" = "/user/{user_id}/my-apps",
 *     "https://www.drupal.org/link-relations/create" = "/user/{user_id}/my-apps"
 *   }
 * )
 */
class MyAppsRestResource extends ResourceBase {

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $currentRequest;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The entity storage class.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $storage;

    /**
     * Information about the entity type.
     *
     * @var \Drupal\Core\Entity\EntityTypeInterface
     */
    protected $entityType;
    
    /**
     * The SDK Connector service.
     *
     * @var \Drupal\apigee_edge\SDKConnectorInterface
     */
    protected $sdkConnector;
    
    /**
     * The SDK Connector service.
     *
     * @var \ Drupal\Core\Config\ConfigFactory
     */
    protected $configFactory;

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
     * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
     *   The entity type.
     * @param \Drupal\Core\Entity\EntityStorageInterface $storage
     *   The entity storage.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
     *   The SDK Connector service.
     * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
     *   The request stack object.
     */
    public function __construct(
    array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, SDKConnectorInterface $sdk_connector, ConfigFactory $config_factory,Request $currentRequest) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->currentRequest = $currentRequest;
        $this->entityType = $entity_type;
        $this->storage = $storage;
        $this->entityTypeManager = $entity_type_manager;
        $this->sdkConnector = $sdk_connector;
        $this->configFactory = $config_factory;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        $entity_type = $container->get('entity_type.manager')->getDefinition('developer_app');
        return new static(
                $configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $container->get('logger.factory')->get('example_rest'), $container->get('current_user'), $entity_type, $container->get('entity.manager')->getStorage($entity_type->id()), $container->get('entity.manager'), $container->get('apigee_edge.sdk_connector'),$container->get('config.factory'), $container->get('request_stack')->getCurrentRequest()
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
        $output = array();
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $userId = $this->currentRequest->get('user_id');
        $headers = [];
        $user = User::load($userId);
        $developerId = $user->get('apigee_edge_developer_id')->value;
        // If developer id can not be retrieved for a Drupal user it means that
        // either there is connection error or the site is out of sync with
        // Apigee Edge.
//    if ($developerId === NULL) {
//      throw new DeveloperDoesNotExistException($user->getEmail());
//    }

        $query = $this->storage->getQuery()
                ->condition('developerId', $developerId);
        $query->tableSort($headers);
        $entity_ids = $query->execute();

        $output['app_list'] = $this->storage->loadMultiple($entity_ids);
        $response = new ResourceResponse($output);
        $response->addCacheableDependency($output);
        return $response;
    }
    
    
    /**
     * Responds to POST requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function post($userId,$data) {
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }
        $user = User::load($userId);
        $developerId = $user->get('apigee_edge_developer_id')->value;
        $entity = $this->entityTypeManager->getStorage('developer_app')->create();
        $entity->setOwnerId($userId);
        foreach($data as $data_key => $data_value) {
            if($data_key != "api_products") {
                $entity->set($data_key,$data_value);
            }
        }
        
        
        $entity->save();
        $output['app_detail'] = $entity;
        $config = $this->configFactory->get('apigee_edge.common_app_settings');
        $dacc = new DeveloperAppCredentialController($this->sdkConnector->getOrganization(), $entity->getDeveloperId(), $entity->getName(), $this->sdkConnector->getClient());

        /** @var \Apigee\Edge\Api\Management\Entity\AppCredential[] $credentials */
        $credentials = $entity->getCredentials();
        /** @var \Apigee\Edge\Api\Management\Entity\AppCredential $credential */
        $credential = reset($credentials);

        $credential_lifetime = $this->configFactory->get('apigee_edge.developer_app_settings')->get('credential_lifetime');
        $products = $data['api_products'];
        if ($credential_lifetime === 0) {
            $dacc->addProducts($credential->id(), $products);
        } else {
            $dacc->delete($credential->id());
            // The value of -1 indicates no set expiry. But the value of 0 is not
            // acceptable by the server (InvalidValueForExpiresIn).
            $dacc->generate($products, $entity->getAttributes(), $entity->getCallbackUrl(), [], $credential_lifetime * 86400000);
        }
         return new ResourceResponse($output);
    }

}
