<?php

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Grid as SubscriptionsGrid;

class Grid extends SubscriptionsGrid
{
    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        Session $customerSession,
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function prepareUpdateUrl()
    {
        $this->data['config']['filter_url_params']['customer_id'] = $this->customerSession->getCustomer()->getId();
        parent::prepareUpdateUrl();
    }
}
