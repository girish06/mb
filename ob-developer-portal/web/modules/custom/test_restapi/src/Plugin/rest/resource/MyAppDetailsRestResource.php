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
use Apigee\Edge\Api\Management\Serializer\AppCredentialSerializer;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\apigee_edge\Entity\Controller\DeveloperAppCredentialController;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "my_apps_details",
 *   label = @Translation("Get the App Details for specific user"),
 *   uri_paths = {
 *     "canonical" = "/user/{user_id}/my-apps/{app}"
 *   }
 * )
 */
class MyAppDetailsRestResource extends ResourceBase {

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
     * @param \Drupal\Core\Config\ConfigFactory $config_factory
     *   Config factory.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
     *   The SDK Connector service.
     * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
     *   The request stack object.
     */
    public function __construct(
    array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, 
            LoggerInterface $logger, 
            AccountProxyInterface $current_user, 
            EntityTypeInterface $entity_type, 
            EntityStorageInterface $storage,
            ConfigFactory $config_factory,
            EntityTypeManagerInterface $entity_type_manager, 
            SDKConnectorInterface $sdk_connector, 
            Request $currentRequest) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->currentRequest = $currentRequest;
        $this->entityType = $entity_type;
        $this->storage = $storage;
        $this->configFactory = $config_factory;
        $this->entityTypeManager = $entity_type_manager;
        $this->sdkConnector = $sdk_connector;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        $entity_type = $container->get('entity_type.manager')->getDefinition('developer_app');
        $entity = $container->get('entity_type.manager')->getDefinition('developer_app');
        return new static(
                $configuration, $plugin_id, $plugin_definition, 
                $container->getParameter('serializer.formats'), 
                $container->get('logger.factory')->get('example_rest'), 
                $container->get('current_user'), $entity_type, 
                $container->get('entity.manager')->getStorage($entity_type->id()), 
                $container->get('config.factory'),            
                $container->get('entity_type.manager'), 
                $container->get('apigee_edge.sdk_connector'),
                $container->get('request_stack')->getCurrentRequest()
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
        $expand = $this->currentRequest->query->get('expand');
        $user = User::load($userId);
        $currentProductIds = [];
        $developerId = $user->get('apigee_edge_developer_id')->value;
        $appId = $this->currentRequest->get('app');
        $query = \Drupal::entityQuery('developer_app')
                ->condition('developerId', $developerId);
        $query_details = $query->execute();
        if (in_array($appId, array_keys($query_details))) {
            $entity = $this->storage->load($appId);
            $output['app_details'] = $entity;
            if (!empty(expand) && $expand) {
                foreach ($entity->getCredentials() as $credential) {
                    $serializer = new AppCredentialSerializer();
                    // Convert app entity to an array.
                    $normalized = (array) $serializer->normalize($credential);
                    $output['credential']['consumer_key'] = $normalized['consumerKey'];
                    $output['credential']['consumer_secret_key'] = $normalized['consumerSecret'];
                    $output['credential']['credential_status'] = $normalized['status'];
                    $output['credential']['issued_at'] = $this->dateCalculator($normalized['issuedAt']);
                    $output['credential']['expires_at'] = $this->dateCalculator($normalized['expiresAt']);
                    foreach ($credential->getApiProducts() as $product) {
                        $currentProductIds[] = $product->getApiproduct();
                        $output['credential']['selected_apis'] = $currentProductIds;
                    }
                }
            }
        } else {
            $output['message'] = t("You don't have permission to view this app");
        }

        $response = new ResourceResponse($output);
        $response->addCacheableDependency($output);
        return $response;
    }

    /**
     * Responds to PATCH requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function patch($userId, $app, $data) {
        $code = 400;
        $output = [];
        $user = User::load($userId);
        $developerId = $user->get('apigee_edge_developer_id')->value;
        $appId = $this->currentRequest->get('app');
        $query = \Drupal::entityQuery('developer_app')
                ->condition('developerId', $developerId);
        $query_details = $query->execute();
        if (in_array($appId, array_keys($query_details))) {
            $entity = $this->storage->load($appId);
            $code = 200;
            foreach ($data as $data_key => $data_value) {
                if ($data_key != "api_products") {
                    $entity->set($data_key, $data_value);
                }
            }
            $dacc = new DeveloperAppCredentialController($this->sdkConnector->getOrganization(), $entity->getDeveloperId(), $entity->getName(), $this->sdkConnector->getClient());
            foreach ($entity->getCredentials() as $original_credential) {
                try {
                    $original_api_product_names = [];
                    // Cast it to array to be able handle the same way the single- and
                    // multi-select configuration.
                    $apiProducts = [];
                    foreach($data['api_products'] as $value) {
                        $apiProducts[$value] = $value;
                    }
                    \Drupal::logger('test_save')->notice('<pre>' . print_r($data['api_products'], TRUE) . '<pre>');
                    $new_api_product_names = $apiProducts;
                    \Drupal::logger('test_save')->notice('<pre>' . print_r($new_api_product_names, TRUE) . '<pre>');
                    foreach ($original_credential->getApiProducts() as $original_api_product) {
                        //$original_api_product_names[] = $original_api_product->getApiproduct();
                        $dacc->deleteApiProduct($original_credential->getConsumerKey(), $original_api_product->getApiproduct());
                    }

//                    if (array_diff($original_api_product_names, $new_api_product_names)) {
//                        foreach (array_diff($original_api_product_names, $new_api_product_names) as $api_product_to_remove) {
//                            $dacc->deleteApiProduct($original_credential->getConsumerKey(), $api_product_to_remove);
//                        }
//                    }
//                    if (array_diff($new_api_product_names, $original_api_product_names)) {
//                        $dacc->addProducts($original_credential->getConsumerKey(), array_values(array_diff($new_api_product_names, $original_api_product_names)));
//                    }
                      $dacc->addProducts($original_credential->getConsumerKey(), array_values($new_api_product_names));
                      $output['message'] = t("App Details has been successfully updated.");
                    break;
                } catch (\Exception $exception) {
                    $output['message'] = t("Could not update credential's product list.", ['@consumer_key' => $original_credential]);
                    watchdog_exception('apigee_edge', $exception);
                }
            }
        }else{
            $output['message'] = "You don't have permisiion to change the App Details";
        }
        
        $entity->save();
        return new ResourceResponse($output, $code);
    }

    /**
     * Responds to DELETE requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function delete() {
        //throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('No Data');
        $userId = $this->currentRequest->get('user_id');
        $user = User::load($userId);
        $developerId = $user->get('apigee_edge_developer_id')->value;
        $appId = $this->currentRequest->get('app');
        $query = \Drupal::entityQuery('developer_app')
                ->condition('developerId', $developerId);
        $query_details = $query->execute();
        if (in_array($appId, array_keys($query_details))) {
            $this->storage->load($appId)->delete();
        }

        return new ModifiedResourceResponse(NULL, 204);
    }

    private function dateCalculator(int $value) {
        $dateFormatter = \Drupal::service('date.formatter');
        //$value = strtotime($value);
        if ($value !== -1) {
            $time_diff = \Drupal::time()->getRequestTime() - intval($value / 1000);
            if ($time_diff > 0) {
                $value = t('@time ago', ['@time' => $dateFormatter->formatTimeDiffSince(intval($value / 1000))]);
            } else {
                $value = t('@time hence', ['@time' => $dateFormatter->formatTimeDiffUntil(intval($value / 1000))]);
            }
        } else {
            $value = t('Never');
        }
        return $value;
    }

}
