<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\ProductBillingFrequency;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;

/**
 * MassDelete action for linked products of billing frequency
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'TNW_Subscriptions::BillingFrequency_save';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @inheritDoc
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        Filter $filter
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filter = $filter;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if (!($this->getRequest()->getParam('isAjax'))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }

        try {
            $productBillingFrequencyIds = $this->getAffectedIds();

            $searchCriteria = $this->criteriaBuilder->addFilter(
                'id',
                $productBillingFrequencyIds,
                'in'
            )->addFilter(
                ProductBillingFrequencyInterface::SUBSCRIPTION_PROFILE_ID_FOR_GRID,
                true,
                'null'
            )->create();
            $productBillingFrequencies = $this->productBillingFrequencyRepository->getList($searchCriteria);
            foreach ($productBillingFrequencies->getItems() as $productBillingFrequency) {
                $this->productBillingFrequencyRepository->delete($productBillingFrequency);
            }
            $unlinkedQty = $productBillingFrequencies->getTotalCount();
            $messages[] = __('%1 product(s) successfully unlinked.', $unlinkedQty);
            if ($unlinkedQty < count($productBillingFrequencyIds)) {
                $messages[] = __(
                    '%1 linked product(s) with active subscription profiles were not unlinked',
                    count($productBillingFrequencyIds) - $unlinkedQty
                );
            }

        } catch (LocalizedException $e) {
            $messages[] =$e->getMessage();
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
     * Get product billing frequency ids for bulk unlinking
     * @return int[]
     * @throws LocalizedException
     */
    public function getAffectedIds()
    {
        $this->filter->applySelectionOnTargetProvider();
        $component = $this->filter->getComponent();
        $dataProvider = $component->getContext()->getDataProvider();
        $dataProvider->setLimit(0, false);
        $searchResult = $dataProvider->getSearchResult();
        return array_map(function (DocumentInterface $item) {
            return $item->getData('id');
        }, $searchResult->getItems());
    }
}
