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
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
                if ($paramName == 'status') {
                    $paramValue = explode(',', $paramValue);
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)
                            ->setConditionType('nin')->create()
                    );
                } else {
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)
                            ->setConditionType('eq')->create()
                    );
                }
            }
        }
    }
}
