<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Class DataBuilder
 * @package TNW\Subscriptions\Model\Payment\Paypal
 */
class DataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var \Magento\Paypal\Model\PayflowConfigFactory
     */
    private $configFactory;

    /**
     * @var
     */
    private $methodCode;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * DataBuilder constructor.
     * @param \Magento\Paypal\Model\PayflowConfigFactory $configFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     */
    public function __construct(
        \Magento\Paypal\Model\PayflowConfigFactory $configFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        SubscriptionConfig $subscriptionConfig,
        Manager $manager
    ) {
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->scopeConfig = $scopeConfig;
        $this->configFactory = $configFactory;
        parent::__construct($subscriptionConfig, $manager);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $paymentData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function build($quote, $paymentInfo)
    {
        $paymentData = $quote->getPayment()->getData();
        $amount = $this->getAmount($quote);
        $storeId = $quote->getStoreId();
        $this->methodCode = $quote->getPayment()->getMethod();
        if (!$quote->getPayment()->getMethod()) {
            $this->methodCode = $paymentInfo['method'];
            $quote->getPayment()->setMethod($this->methodCode);
        }
        $config = $this->configFactory->create();
        $config->setStoreId($storeId);
        $config->setMethodInstance($quote->getPayment()->getMethodInstance());
        $config->setMethod($this->methodCode);
        $orderIncrementId = $quote->getReservedOrderId();
        $billing = $quote->getBillingAddress();
        $totals = $quote->getTotals();
        $token = $paymentData['additional_information'][\Magento\Paypal\Model\Payflowpro::PNREF];
        $requestData = [
            'user' => $this->getConfigData('user'),
            'vendor' => $this->getConfigData('vendor'),
            'partner' => $this->getConfigData('partner'),
            'pwd' => $this->getConfigData('pwd'),
            'verbosity' => $this->getConfigData('verbosity'),
            'BUTTONSOURCE' => $config->getBuildNotationCode(),
            'tender' => \Magento\Paypal\Model\Payflowpro::TENDER_CC,
            'custref' => $orderIncrementId,
            'invnum' => $orderIncrementId,
            'comment1' => $orderIncrementId,
            'email' => $quote->getCustomerEmail(),
            'firstname' => $billing->getFirstname(),
            'lastname' => $billing->getLastname(),
            'street' =>  implode(' ', $billing->getStreet()),
            'city' => $billing->getCity(),
            'state' =>  $billing->getRegionCode(),
            'zip' => $billing->getPostcode(),
            'county' => $billing->getCountryId(),
            'trxtype' => \Magento\Paypal\Model\Payflowpro::TRXTYPE_AUTH_ONLY,
            'origid' => $token,
            'amt' => $this->formatPrice($amount),
            'currency' => $quote->getBaseCurrencyCode(),
            'itemamt' => $this->formatPrice($amount),
            'taxamt' => $this->formatPrice($totals['tax']->getValue()),
            'freightamt' => isset($totals['shipping']) ? $this->formatPrice($totals['shipping']->getValue()) : 0,
            'discount' => $this->formatPrice(0)
        ];
        $shipping = $quote->getShippingAddress();
        if (!empty($shipping)) {
            $requestData['shiptofirstname'] =
                $shipping->getFirstname();
            $requestData['shiptolastname'] =
                $shipping->getLastname();
            $requestData['shiptostreet'] =
                implode(' ', $shipping->getStreet());
            $requestData['shiptocity'] =
                $shipping->getCity();
            $requestData['shiptostate'] =
                $shipping->getRegionCode();
            $requestData['shiptozip'] =
                $shipping->getPostcode();
            $requestData['shiptocountry'] =
                $shipping->getCountryId();
        }
        return [
            'requestData' => $requestData,
            'config' => $config
        ];
    }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    private function getConfigData($field, $storeId = null)
    {
        $path = 'payment/' . $this->methodCode . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
}
