<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill\CollectionFactory;
use TNW\Subscriptions\Api\Data\ReBillInterface as Model;
use TNW\Subscriptions\Api\Data\ReBillInterfaceFactory as ModelFactory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill as ResourceModel;
use TNW\Subscriptions\Api\ReBillRepositoryInterface;

/**
 * Class ReBillRepository - used to handle the data operations with subscription re-bills
 */
class ReBillRepository implements ReBillRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var ReBillFactory
     */
    private $reBillFactory;

    /**
     * ReBillRepository constructor.
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     * @param ModelFactory $reBillFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceModel $resourceModel,
        ModelFactory $reBillFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->reBillFactory = $reBillFactory;
    }

    /**
     * @param Model $reBill
     * @return Model
     * @throws CouldNotSaveException
     */
    public function save(Model $reBill)
    {
        try {
            if ($reBill->getCustomerId() && $reBill->getToken()) {
                $this->resourceModel->save($reBill);
            } else {
                throw new CouldNotSaveException(
                    __('Could not save the subscription re-bill. Token not generated for customer.')
                );
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription re-bill: %1',
                $e->getMessage()
            ));
        }
        return $reBill;
    }

    /**
     * @param $reBillId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($reBillId)
    {
        $reBill = $this->reBillFactory->create();
        $this->resourceModel->load($reBill, $reBillId);
        if (!$reBill->getId()) {
            throw new NoSuchEntityException(__(
                'Subscriotion ReBill with id "%1" does not exist.',
                $reBillId
            ));
        }

        return $reBill;
    }

    /**
     * @param $reBillToken
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getByToken($reBillToken)
    {
        $reBill = $this->reBillFactory->create();
        $this->resourceModel->load($reBill, $reBillToken, 'token');
        if (!$reBill->getId()) {
            throw new NoSuchEntityException(__(
                'Subscription ReBill with token "%1" does not exist.',
                $reBillToken
            ));
        }

        return $reBill;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        return $collection;
    }

    /**
     * @param Model $reBill
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Model $reBill)
    {
        try {
            $this->resourceModel->delete($reBill);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription profile re-bill: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param $token
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteByToken($token)
    {
        return $this->delete($this->getByToken($token));
    }
}
