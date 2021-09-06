<?php

namespace TNW\Subscriptions\Model\Queue;

use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;

/**
 * Class ProfitManager to publish profiles ids where need to calculate profit
 */
class ProfitManager
{
    const TOPIC_NAME = 'tnw.profile.profit';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityGenerator;

    /**
     * @var Json
     */
    private $serializer;

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
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * ProfitManager constructor.
     * @param PublisherInterface $publisher
     * @param IdentityGeneratorInterface $identityGenerator
     * @param Json $serializer
     */
    public function __construct(
        PublisherInterface $publisher,
        IdentityGeneratorInterface $identityGenerator,
        Json $serializer,
        JsonFactory $jsonFactory,
        BulkManagementInterface $bulkManagement,
        UserContextInterface $userContext,
        OperationInterfaceFactory $operationFactory
    ) {
        $this->publisher = $publisher;
        $this->identityGenerator = $identityGenerator;
        $this->serializer = $serializer;
        $this->jsonFactory = $jsonFactory;
        $this->bulkManagement = $bulkManagement;
        $this->userContext = $userContext;
        $this->operationFactory = $operationFactory;
    }

    /**
     * @param $profiles
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function setProfilesToCalculateProfit($profiles)
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        try {
            $this->publish($profiles);
            $messages[] = __('%1 selected profiles were added to queue', count($profiles));
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
     * Split ids in chunks and schedule bulk link
     *
     * @param $profilesIds
     * @throws LocalizedException
     */
    private function publish($profilesIds)
    {
        $profilesIdsChunks = array_chunk($profilesIds, $this->bulkSize);
        $bulkUuid = $this->identityGenerator->generateId();
        $bulkDescription = __('Add %1 profile(s) to recalculate profit', count($profilesIds));
        $operations = [];
        foreach ($profilesIdsChunks as $profileIdsChunk) {
            $operations[] = $this->makeOperation($bulkUuid, $profileIdsChunk);
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
     *
     * @param $bulkUuid
     * @param $profilesIds
     * @return \Magento\AsynchronousOperations\Api\Data\OperationInterface
     */
    private function makeOperation(
        $bulkUuid,
        $profilesIds
    ) {
        $dataToEncode = [
            'profiles_ids' => $profilesIds,
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => self::TOPIC_NAME,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }
}
