<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\ProductBillingFrequency;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Action for creating bulk operations of linking products to billing frequency
 */
class AddLinkedProducts extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'TNW_Subscriptions::BillingFrequency_save';

    /**
     * Queue topic name
     */
    const TOPIC_NAME = 'tnw.products.link';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var int
     */
    private $bulkSize = 50;

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * AddLinkedProducts constructor.
     * @param Context $context
     * @param Filter $filter
     * @param JsonFactory $jsonFactory
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param SerializerInterface $serializer
     * @param UserContextInterface $userContext
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        JsonFactory $jsonFactory,
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        SerializerInterface $serializer,
        UserContextInterface $userContext,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->jsonFactory = $jsonFactory;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        try {
            $productIds = $this->getAffectedProductIds();
            $frequencyId = $this->getRequest()->getParam('frequency_id');
            $this->publish($productIds, $frequencyId);
            $messages[] = __('%1 selected products were added to queue', count($productIds));
        } catch (LocalizedException $e) {
            $messages[] = $e->getMessage();
            $error = true;
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }

    /**
     * Get product ids for bulk linking
     * @return int[]
     * @throws LocalizedException
     */
    public function getAffectedProductIds()
    {
        $this->filter->getComponent()->getContext()->getDataProvider()->prepareCollection();
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        return array_map(function (ProductInterface $item) {
            return $item->getId();
        }, $collection->getItems());
    }

    /**
     * Split ids in chunks and schedule bulk link
     * @param $productIds
     * @param $frequencyId
     * @throws LocalizedException
     */
    private function publish($productIds, $frequencyId)
    {
        $productIdsChunks = array_chunk($productIds, $this->bulkSize);
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Add %1 linked product(s) to Billing Frequency', count($productIds));
        $operations = [];
        foreach ($productIdsChunks as $productIdsChunk) {
            $operations[] = $this->makeOperation($bulkUuid, $productIdsChunk, $frequencyId);
        }
        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription,
                $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

    /**
     * Prepare operation object for chunk
     * @param $bulkUuid
     * @param $productIds
     * @param $frequencyId
     * @return OperationInterface
     */
    private function makeOperation(
        $bulkUuid,
        $productIds,
        $frequencyId
    ) {
        $dataToEncode = [
            'meta_information' => __('linking product(s) to Billing Frequency'),
            'product_ids' => $productIds,
            'frequency_id' => $frequencyId
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => self::TOPIC_NAME,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => BulkOperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }
}
