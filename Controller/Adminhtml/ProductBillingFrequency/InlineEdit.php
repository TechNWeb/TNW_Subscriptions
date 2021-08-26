<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\ProductBillingFrequency;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;

/**
 * Action for inline grid editing of linked products to billing frequency
 */
class InlineEdit extends Action implements HttpPostActionInterface
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
    private $searchCriteriaBuilder;

    /**
     * InlineEdit constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        $allowedFields = [
            'price',
            'initial_fee',
            'preset_qty'
        ];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::ID,
            array_keys($postItems),
            'in'
        )->create();

        try {
            $productBillingFrequencies = $this->productBillingFrequencyRepository->getList($searchCriteria)->getItems();
            foreach ($productBillingFrequencies as $productBillingFrequency) {
                if (!empty($postItems[$productBillingFrequency->getId()])) {
                    foreach ($allowedFields as $allowedField) {
                        if (isset($postItems[$productBillingFrequency->getId()][$allowedField])) {
                            $productBillingFrequency->setData(
                                $allowedField,
                                $postItems[$productBillingFrequency->getId()][$allowedField]
                            );
                        }
                    }
                    $this->productBillingFrequencyRepository->save($productBillingFrequency);
                }
            }
        } catch (LocalizedException $e) {
            return $resultJson->setData(
                [
                    'messages' => [$e->getMessage()],
                    'error' => true,
                ]
            );
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
