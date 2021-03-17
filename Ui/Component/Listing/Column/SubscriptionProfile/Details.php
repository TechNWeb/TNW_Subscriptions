<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Block\Subscription\History;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;

/**
 * Class Details - ui component
 */
class Details extends Column
{
    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @var Config
     */
    private $addressConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var ManagerConfigurable
     */
    private $managerConfigurable;
    /**
     * @var StatusManager
     */
    private $statusManager;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Details constructor.
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerConfigurable $managerConfigurable
     * @param StatusManager $statusManager
     * @param ProfileStatus $profileStatus
     * @param PaymentConfig $paymentConfig
     * @param UrlInterface $urlBuilder
     * @param Config $addressConfig
     * @param Image $imageHelper
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        ManagerConfigurable $managerConfigurable,
        StatusManager $statusManager,
        ProfileStatus $profileStatus,
        PaymentConfig $paymentConfig,
        UrlInterface $urlBuilder,
        Config $addressConfig,
        Image $imageHelper,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->managerConfigurable = $managerConfigurable;
        $this->profileStatus = $profileStatus;
        $this->paymentConfig = $paymentConfig;
        $this->addressConfig = $addressConfig;
        $this->imageHelper = $imageHelper;
        $this->statusManager = $statusManager;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $itemId = $item[SubscriptionProfileInterface::ID];
                if (isset($itemId)) {
                    $profileProduct = $this->getSubscriptionProfileProduct($itemId);
                    if (!$profileProduct) {
                        continue;
                    }
                    $product = $this->getProduct($profileProduct->getMagentoProductId());
                    $imageHelper = $this->imageHelper->init($product, 'mini_cart_product_thumbnail');
                    $item['subscription_product'] = [
                        'name' => $profileProduct->getName(),
                        'qty' => $profileProduct->getQty(),
                        'short_description' => $product->getShortDescription(),
                        'img_src' => $imageHelper->getUrl(),
                        'img_alt' => $profileProduct->getName(),
                        'configurable_options' => $this->managerConfigurable
                            ->getConfigurableOptionsData($profileProduct)
                    ];
                    $item['term_label'] = $this->getTerm($itemId);
                    $item['status_label'] = $this->profileStatus->getLabelByValue($item['status']);
                    if ($this->getIsVirtual($itemId)) {
                        $item['is_virtual'] = true;
                    } else {
                        $item['shipping_address'] = $this->getSubscriptionAddressHtml($itemId, 'shipping');
                        $item['shipping_method'] = $this->getSubscriptionShippingMethod($itemId);
                    }
                    $item['billing_address'] = $this->getSubscriptionAddressHtml($itemId, 'billing');
                    $item['payment_method'] = $this->getPaymentMethodTitle($itemId);
                    $item['payment_description'] = $this->getSubscriptionPaymentDescription($itemId);
                    //TODO: next date should consider locale & timezones
                    $nextDate = new \DateTime($item['next_billing_cycle_date']);
                    $item['next_date'] = $nextDate->format('M d, Y');
                    $item['profile_actions'] = $this->getProfileActions($itemId);
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionProfileProduct($id)
    {
        $profile = $this->getSubscriptionProfile($id);
        if ($profile) {
            foreach ($profile->getProducts() as $product) {
                return $product;
            }
        }
        return null;
    }

    /**
     * @param $id
     * @param $type
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionAddressHtml($id, $type)
    {
        $profile = $this->getSubscriptionProfile($id);
        $addressData = QuoteAddress::ADDRESS_TYPE_SHIPPING === $type
            ? $profile->getShippingAddress()->getData()
            : $profile->getBillingAddress()->getData();
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($addressData);
    }

    /**
     * @param $id
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getIsVirtual($id)
    {
        return $this->getSubscriptionProfile($id)->getIsVirtual();
    }

    /**
     * @param $id
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionShippingMethod($id)
    {
        return $this->getSubscriptionProfile($id)->getShippingDescription();
    }

    /**
     * @param $id
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionPaymentDescription($id)
    {
        $additionalInfo = $this->getSubscriptionProfile($id)->getPayment()->getPaymentAdditionalInfo();
        if (!$additionalInfo) {
            return false;
        }
        $additionalInfo = (array) json_decode($additionalInfo);
        $result = ['currency' => $this->getSubscriptionProfile($id)->getProfileCurrencyCode()];
        $resultAdditional = [];
        if ($this->getCreditCardTypeLabel($additionalInfo) && $this->getCreditCardNumber($additionalInfo)) {
            $resultAdditional =
                [
                    'cc_type' => $this->getCreditCardTypeLabel($additionalInfo),
                    'cc_number' => $this->getCreditCardNumber($additionalInfo),
                    'cc_exp' => $this->getCreditCardExpDate($additionalInfo)
                ];
        }
        if ($this->getPurchaseOrderNumber($additionalInfo)) {
            $resultAdditional =
                [
                    'po_number' => $this->getPurchaseOrderNumber($additionalInfo)
                ];
        }
        return array_merge($result, $resultAdditional);
    }

    /**
     * Returns credit card exp. date
     * @param $additionalInfo
     * @return string
     */
    public function getCreditCardExpDate($additionalInfo)
    {
        $creditCardExpDate = '';
        if (isset($additionalInfo['cc_exp_month']) && isset($additionalInfo['cc_exp_year'])) {
            $ccExpMonth = (int) $additionalInfo['cc_exp_month'];
            $ccExpYear = (int) $additionalInfo['cc_exp_year'];
            $creditCardExpDate = "{$ccExpMonth}/{$ccExpYear}";
        }

        return $creditCardExpDate;
    }

    /**
     * Returns credit card number
     * @param $additionalInfo
     * @return string
     */
    public function getCreditCardNumber($additionalInfo)
    {
        $creditCardNumber = '';
        if (isset($additionalInfo['cc_last_4'])) {
            $creditCardNumber = sprintf('XXXX %s', $additionalInfo['cc_last_4']);
        }

        return $creditCardNumber;
    }

    /**
     * Returns credit card type label
     * @param $additionalInfo
     * @return string
     */
    public function getCreditCardTypeLabel($additionalInfo)
    {
        $creditCardTypeLabel = __('Unknown Cart Type');
        if (isset($additionalInfo['cc_type'])) {
            $ccTypes = $this->paymentConfig->getCcTypes();
            foreach ($ccTypes as $key => $label) {
                if ($key == $additionalInfo['cc_type']) {
                    $creditCardTypeLabel = $label;
                }
            }
        }

        return $creditCardTypeLabel;
    }

    /**
     * @param $additionalInfo
     * @return string
     */
    public function getPurchaseOrderNumber($additionalInfo)
    {
        $poNumber = '';
        if (isset($additionalInfo['po_number'])) {
            $poNumber = $additionalInfo['po_number'];
        }

        return $poNumber;
    }

    /**
     * Return subscription payment description
     *
     * @param $id
     * @return null|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentMethodTitle($id)
    {
        $path = 'payment/' . $this->getSubscriptionProfile($id)->getPayment()->getEngineCode() . '/title';
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProfileActions($id)
    {
        $profile = $this->getSubscriptionProfile($id);
        if (!$profile) {
            return [];
        }
        $result = [
            [
                'type' => 'edit',
                'label' => __('edit'),
                'href' => $this->urlBuilder->getUrl(
                    'tnw_subscriptions/subscription/edit/',
                    [
                        'entity_id' => $id
                    ]
                )
            ]
        ];
        if ($this->statusManager->canHoldSubscription($profile)) {
            $result[] = [
                'type' => 'hold',
                'label' => __('hold'),
                'title' => __('Warning!'),
                'message' => __('Are you sure? While "On Hold" you will not be billed.'),
                'href' => $this->urlBuilder->getUrl(
                    'tnw_subscriptions/subscription_actions/updateStatus',
                    [
                        'entity_id' => $id,
                        'status' => ProfileStatus::STATUS_HOLDED,
                        'redirect' => History::REDIRECT,
                    ]
                )
            ];
        }
        if ($this->statusManager->canReActiveSubscription($profile)) {
            $result[] = [
                'type' => 'reactivate',
                'label' => __('resume'),
                'href' => $this->urlBuilder->getUrl(
                    'tnw_subscriptions/subscription_actions/updateStatus',
                    [
                        'entity_id' => $id,
                        'status' => ProfileStatus::STATUS_ACTIVE,
                        'redirect' => History::REDIRECT,
                    ]
                )
            ];
        }
        if ($this->statusManager->canCancelSubscription($profile)) {
            $result[] = [
                'type' => 'cancel',
                'label' => __('cancel'),
                'title' => __('Warning!'),
                'message' => __('Are you sure? This action cannot be reversed.'),
                'href' => $this->urlBuilder->getUrl(
                    'tnw_subscriptions/subscription_actions/updateStatus',
                    [
                        'entity_id' => $id,
                        'status' => ProfileStatus::STATUS_CANCELED,
                        'redirect' => History::REDIRECT,
                    ]
                )
            ];
        }
        return $result;
    }

    /**
     * @param $id
     * @return SubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionProfile($id)
    {
        return $this->subscriptionProfileRepository->getById($id);
    }

    /**
     * @param $id
     * @return \Magento\Framework\Phrase|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getTerm($id)
    {
        $label = '';
        $profile = $this->subscriptionProfileRepository->getById($id);
        if ($profile) {
            if ($profile->getTerm()) {
                $label = __('until canceled');
            } elseif ((int)$profile->getTotalBillingCycles() === 1) {
                $label = __('bill once');
            } else {
                $label = __('bill %1 times', $profile->getTotalBillingCycles());
            }
        }
        return $label;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProduct($id)
    {
        return $this->productRepository->getById($id);
    }
}
