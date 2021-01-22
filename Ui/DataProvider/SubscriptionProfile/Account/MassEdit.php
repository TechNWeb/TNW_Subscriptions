<?php

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Account;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class MassEdit extends DataProvider
{
    const FORM_NAME = 'tnw_subscriptionprofile_account_massedit_form';

    const PAYMENT_DETAILS_FIELDSET = 'wizard_modal.step-wizard.payment_method';

    /**
     * @var PoolInterface
     */
    private PoolInterface $modifiersPool;

    /**
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * MassEdit constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param FilterBuilder $filterBuilder
     * @param PoolInterface $modifiersPool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        FilterBuilder $filterBuilder,
        PoolInterface $modifiersPool,
        array $meta = [],
        array $data = []
    ) {
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
        $this->modifiersPool = $modifiersPool;
        $this->arrayManager = $arrayManager;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            if (method_exists($modifier, 'setPaymentFormName')) {
                $modifier->setPaymentFormName($this::FORM_NAME);
            }
            if (method_exists($modifier, 'setAdditionalNamespace')) {
                $modifier->setAdditionalNamespace(self::PAYMENT_DETAILS_FIELDSET);
            }
//            if (method_exists($modifier, 'setListens')) {
//                $modifier->setListens([]);
//            }
            $meta = $modifier->modifyMeta($meta);
        }

        $meta = $this->arrayManager->move(
            'payment_information',
            'wizard_modal/children/step-wizard/children/payment_method/children/payment_information',
            $meta
        );

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            if (method_exists($modifier, 'modifyConfigData')) {
                $configData = $modifier->modifyConfigData($configData);
            }
        }

        $configData['submit_url'] = $this->urlBuilder->getUrl(
            '*/subscription_massedit/save'
        );

//        $configData['process_url'] = $this->urlBuilder->getUrl(
//            '*/subscription_massedit/processpayment'
//        );

        return $configData;
    }
}
