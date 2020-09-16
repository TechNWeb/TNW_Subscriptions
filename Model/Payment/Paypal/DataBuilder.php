<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class DataBuilder
 * @package TNW\Subscriptions\Model\Payment\Paypal
 */
class DataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var mixed
     */
    private $config;

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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     * @param ObjectManager $objectManager
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        ObjectManager $objectManager,
        ModuleManager $moduleManager
    ) {
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->config = $objectManager->get("Magento\Paypal\Model\PayflowConfig");
        }
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->scopeConfig = $scopeConfig;
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
        $config = $this->config;
        $config->setStoreId($storeId);
        if (!$quote->getPayment()->getQuote()) {
            $quote->getPayment()->setQuote($quote);
        }
        $config->setMethodInstance($quote->getPayment()->getMethodInstance());
        $config->setMethod($this->methodCode);
        $orderIncrementId = $quote->getReservedOrderId();
        $billing = $quote->getBillingAddress();
        $totals = $quote->getTotals();
        $token = isset($paymentData['additional_information'][VaultDataBuilder::PNREF])
        ? $paymentData['additional_information'][VaultDataBuilder::PNREF]
        : $paymentInfo['additional_information'][VaultDataBuilder::PNREF];
        $requestData = [
            'user' => $this->getConfigData('user'),
            'vendor' => $this->getConfigData('vendor'),
            'partner' => $this->getConfigData('partner'),
            'pwd' => $this->getConfigData('pwd'),
            'verbosity' => $this->getConfigData('verbosity'),
            'BUTTONSOURCE' => $config->getBuildNotationCode(),
            'tender' => VaultDataBuilder::TENDER_CC,
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
            'trxtype' => VaultDataBuilder::TRXTYPE_AUTH_ONLY,
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
