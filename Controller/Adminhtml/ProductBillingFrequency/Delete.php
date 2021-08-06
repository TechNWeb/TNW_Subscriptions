<?php

namespace TNW\Subscriptions\Controller\Adminhtml\ProductBillingFrequency;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\ProductSubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Delete action for linked products of billing frequency
 */
class Delete extends Action implements HttpPostActionInterface
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var array
     */
    private $searchStatuses = [
        ProfileStatus::STATUS_ACTIVE,
        ProfileStatus::STATUS_TRIAL,
        ProfileStatus::STATUS_HOLDED,
        ProfileStatus::STATUS_PAST_DUE
    ];

    /**
     * @var ProductSubscriptionProfileRepositoryInterface
     */
    private $productSubscriptionProfileRepository;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @inheritDoc
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        UrlInterface $urlBuilder,
        ProductSubscriptionProfileRepositoryInterface $productSubscriptionProfileRepository,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->urlBuilder = $urlBuilder;
        $this->productSubscriptionProfileRepository = $productSubscriptionProfileRepository;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $id = $this->getRequest()->getParam('id');
        if (!($this->getRequest()->getParam('isAjax') && !empty($id))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }

        try {
            $productBillingFrequency = $this->productBillingFrequencyRepository->getById($id);
            $searchCriteria = $this->criteriaBuilder->addFilter(
                'profile_status',
                $this->searchStatuses,
                'in'
            )->addFilter(
                'magento_product_id',
                $productBillingFrequency->getMagentoProductId()
            )->setPageSize(1)->setCurrentPage(1)->create();
            $productSubscriptionProfiles = $this->productSubscriptionProfileRepository->getList($searchCriteria);
            if ($productSubscriptionProfiles->getTotalCount() > 0) {
                $items = $productSubscriptionProfiles->getItems();
                $item = reset($items);
                if ($parentId = $item->getParentId()) {
                    $childSku = $item->getSku();
                    $parentId = $this->productSubscriptionProfileRepository->getById($parentId)->getMagentoProductId();
                }
                $gridUrl = $this->urlBuilder->getUrl(
                    'tnw_subscriptions/subscriptionprofile/index',
                    [
                        'status' => implode(',', $this->searchStatuses),
                        'product_id' => $parentId ?? $productBillingFrequency->getMagentoProductId(),
                        'child_sku' => $childSku ?? null,
                        'billing_frequency_id' => $productBillingFrequency->getBillingFrequencyId()
                    ]
                );
                return $resultJson->setData(
                    [
                        'messages' => [],
                        'grid_url' => $gridUrl,
                        'error' => true,
                    ]
                );
            }
            $this->productBillingFrequencyRepository->delete($productBillingFrequency);
            $messages[] = __('Linked product successfully removed.');

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
}
