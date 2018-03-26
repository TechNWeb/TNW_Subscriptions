<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;

/**
 * Base form modifier to display payment method.
 */
class Base implements PaymentModifierInterface
{
    /**#@+
     * Name of payment information fieldset.
     */
    const PAYMENT_INFORMATION_FIELD_SET_NAME = 'payment_information';
    const SORT_ORDER = 0;
    /**#@-*/

    /**
     * @var Config
     */
    private $config;

    /**
     * Payment form name
     *
     * @var string
     */
    private $paymentFormName;

    /**
     * Additional namespace
     *
     * @var string
     */
    private $additionalNamespace;

    /**
     * Listens
     *
     * @var string
     */
    private $listens;

    /**
     * Profile id
     *
     * @var \TNW\Subscriptions\Model\SubscriptionProfile
     */
    private $profile;

    /**
     * Session
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var OrderRelationManager
     */
    private $relationManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Base constructor.
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Config $config,
        QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->paymentFormName = Payment::DATA_SCOPE_PAYMENT_FORM;
        $this->listens = [
            'checked' => 'saveBilling'
        ];
        $this->profileRepository = $profileRepository;
        $this->relationManager = $relationManager;
        $this->cartRepository = $cartRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyConfigData(array $configData)
    {
        return $configData;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if ($this->isPaymentMethodAvailable()) {
            $meta = array_replace_recursive(
                $meta,
                $this->getPaymentFields()
            );
        }

        return $meta;
    }

    /**
     * Returns payment method code.
     *
     * @return string
     */
    protected function getPaymentCode()
    {
        return '';
    }

    /**
     * Returns payment method title.
     *
     * @return string
     */
    protected function getPaymentTitle()
    {
        return '';
    }

    /**
     * Returns additional fields for payment method (if payment method have it).
     *
     * @return array
     */
    protected function getAdditionalFields()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getListens()
    {
        return $this->listens;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getGrandTotal()
    {
        $grandTotal = 0;
        if ($this->getProfile()) {
            $nextProfileRelation = $this->relationManager
                ->getNextProfileRelation($this->getProfile());

            $grandTotal = $this->cartRepository
                ->get($nextProfileRelation->getMagentoQuoteId())
                ->getBaseGrandTotal();
        } else {
            /** @var \Magento\Quote\Model\Quote $quote */
            foreach ($this->session->getSubQuotes() as $quote) {
                $grandTotal += $quote->getBaseGrandTotal();
            }
        }

        return $grandTotal;
    }

    /**
     * @return string
     */
    protected function getCurrencyCode()
    {
        $currencyCode = '';
        if ($this->getProfile()) {
            $this->getProfile()->getProfileCurrencyCode();
        } else {
            /** @var \Magento\Quote\Model\Quote $quote */
            foreach ($this->session->getSubQuotes() as $quote) {
                $currencyCode = $quote->getBaseCurrencyCode();
            }
        }

        return $currencyCode;
    }

    /**
     * Sets listens
     *
     * @param $listens
     * @return $this
     */
    public function setListens($listens)
    {
        $this->listens = $listens;
        return $this;
    }

    /**
     * Returns metadata for payment method fieldset.
     *
     * @return array
     */
    private function getPaymentFields()
    {
        $additionalConfig = $this->getAdditionalConfig();

        return [
            static::PAYMENT_INFORMATION_FIELD_SET_NAME => [
                'children' => [
                    $this->getPaymentCode() => [
                        'children' => $this->getChildren(),
                        'arguments' => [
                            'data' => [
                                'config' => array_merge(
                                    [
                                        'label' => false,
                                        'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/base',
                                        'collapsible' => false,
                                        'visible' => true,
                                        'opened' => true,
                                        'dataScope' => $this->getPaymentCode(),
                                        'componentType' => Fieldset::NAME,
                                        'additionalClasses' => 'fieldset-wrapper-title',
                                        'sortOrder' => $this::SORT_ORDER,
                                    ],
                                    $additionalConfig
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns metadata for payment method choose field.
     *
     * @return array
     */
    protected function getField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Checkbox::NAME,
                        'componentType' => Field::NAME,
                        'prefer' => 'radio',
                        'description' => $this->getPaymentTitle(),
                        'dataScope' => 'method',
                        'component' => 'TNW_Subscriptions/js/components/extended-checkbox',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/radio',
                        'parentContainer' => static::PAYMENT_INFORMATION_FIELD_SET_NAME,
                        'parentSelections' => static::PAYMENT_INFORMATION_FIELD_SET_NAME,
                        'additionalClasses' => 'payment-checkbox',
                        'dataType' => 'number',
                        'valueMap' => [
                            'false' => '0',
                            'true' => '1',
                        ],
                        'default' => '0',
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns array of child elements.
     *
     * @return array
     */
    protected function getChildren()
    {
        $result = [
            'method' => $this->getField(),
        ];
        $fieldsetName = $this->getFieldsetName();
        $checkBoxName = $fieldsetName . '.method';
        $result['additional_fields'] = [
            'children' => $this->getAdditionalFields(),
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/additional-fields-fieldset',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        'label' => false,
                        'visible' => false,
                        'dataScope' => 'additional',
                        'additionalClasses' => 'payment-additional-fieldset',
                        'collapsible' => false,
                        'opened' => true,
                        'imports' => [
                            'changeVisibility' => $checkBoxName . ':checked'
                        ],
                        'exports' => [
                            'visible' => $fieldsetName . ':checked'
                        ],
                    ],
                ],
            ],
        ];

        return $result;
    }

    /**
     * Returns additional payment method fieldset config.
     *
     * @return array
     */
    protected function getAdditionalConfig()
    {
        return [];
    }

    /**
     * Returns full name of current fieldset.
     *
     * @return string
     */
    protected function getFieldsetName()
    {
        $fieldsetName = $this->getPaymentFormName() .
            '.' . $this->getPaymentFormName() .
            ($this->additionalNamespace ? '.' . $this->additionalNamespace : '') .
            '.' . static::PAYMENT_INFORMATION_FIELD_SET_NAME
            . '.' . $this->getPaymentCode();

        return $fieldsetName;
    }

    /**
     * Sets payment form name
     *
     * @return string
     */
    public function setPaymentFormName($paymentFormName)
    {
        $this->paymentFormName = $paymentFormName;
        return $this;
    }

    /**
     * Returns payment form name
     *
     * @return string
     */
    public function getPaymentFormName()
    {
        return $this->paymentFormName;
    }

    /**
     * Sets additional namespace
     *
     * @return string
     */
    public function setAdditionalNamespace($additionalNamespace)
    {
        $this->additionalNamespace = $additionalNamespace;
        return $this;
    }

    /**
     * Returns profile id
     *
     * @return int|null
     */
    protected function getProfileId()
    {
        $profileId = null;
        if ($this->profile) {
            $profileId = $this->profile->getId();
        }

        return $profileId;
    }

    /**
     * @return \TNW\Subscriptions\Model\SubscriptionProfile
     */
    protected function getProfile()
    {
        return $this->profile;
    }

    /**
     * Sets profile id
     *
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->profile = $this->profileRepository->getById($profileId);
        return $this;
    }

    /**
     * Returns payment method availability.
     *
     * @return bool
     */
    protected function isPaymentMethodAvailable()
    {
        return $this->config->isPaymentMethodAvailableForSubscription(
            $this->getPaymentCode(),
            $this->session->getStoreId()
        );
    }

    /**
     * Returns start on date of profile or max start on date of profile quotes.
     * @return \DateTime
     */
    protected function getValidationDate()
    {
        $nowDate = (new \DateTime())->format('Y-m-d');
        if ($this->getProfile()) {
            $startDates = [
                $nowDate,
                $this->getProfile()->getStartDate(),
            ];
            if ($this->getProfile()->getTrialStartDate()) {
                $startDates[] = $this->getProfile()->getTrialStartDate();
            }
            $nextProfileRelation = $this->relationManager->getNextProfileRelation(
                $this->getProfile()
            );
            if ($nextProfileRelation) {
                $startDates[] = $nextProfileRelation->getScheduledAt();
            }
        } else {
            $startDates[] = $nowDate;
            foreach ($this->session->getSubQuotes() as $quote) {
                $items = $quote->getAllVisibleItems();
                $item = $items ? reset($items) : false;
                /** @var Item $item */
                if ($item) {
                    $startOnPath = Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                        . DIRECTORY_SEPARATOR . Create::UNIQUE
                        . DIRECTORY_SEPARATOR . 'start_on';

                    $startOn = $item->getBuyRequest()->getDataByPath($startOnPath);
                    if ($startOn) {
                        $startDates[] = $startOn;
                    }
                }
            }
        }
        $startDate = max(array_map('strtotime', $startDates));

        return (new \DateTime())->setTimestamp($startDate);
    }
}
