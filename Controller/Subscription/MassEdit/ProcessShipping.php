<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\MassEdit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;

/**
 * Class ProcessShipping - shipping data process
 */
class ProcessShipping extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * ProcessShipping constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SerializerInterface $serializer
     * @param Manager $profileManager
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param AddressRepositoryInterface $customerAddressRepository
     * @param AddressConfig $addressConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SerializerInterface $serializer,
        Manager $profileManager,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        AddressRepositoryInterface $customerAddressRepository,
        AddressConfig $addressConfig
    ) {
        parent::__construct($context);
        $this->customerAddressRepository = $customerAddressRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->profileManager = $profileManager;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->addressConfig = $addressConfig;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $error = false;
        $rates = [];
        $addressLine = '';
        if (array_key_exists('selectedSubsIds', $params) && is_array($params['selectedSubsIds'])) {
            $profile = null;
            foreach ($params['selectedSubsIds'] as $profileId) {
                try {
                    //TODO: add all the products to one profile so that shipping rates are calculated more accurate
                    $profile = $this->subscriptionProfileRepository->getById($profileId);
                    break;
                } catch (\Exception $e) {
                    $error = __('Invalid Subscription Id Provided.');
                }
            }
            if ($profile) {
                if (array_key_exists('shipping_address', $params)
                    && is_array($params['shipping_address'])
                ) {
                    $addressData = [];
                    if (array_key_exists('shipping_address_id', $params['shipping_address'])
                        && $params['shipping_address']['shipping_address_id']
                        && array_key_exists('isNewShippingAddress', $params)
                        && $params['isNewShippingAddress'] == "false"
                    ) {
                        $addressId = $params['shipping_address']['shipping_address_id'];
                        try {
                            //TODO: Re-factor: place this functionality into separate method
                            $address = $this->customerAddressRepository->getById($addressId);
                            $addressData = [
                                'firstname' => $address->getFirstname(),
                                'region_id' => $address->getRegionId(),
                                'country_id' => $address->getCountryId(),
                                'company' => $address->getCompany(),
                                'telephone' => $address->getTelephone(),
                                'fax' => $address->getFax(),
                                'postcode' => $address->getPostcode(),
                                'city' => $address->getCity(),
                                'lastname' => $address->getLastname(),
                                'vat_id' => $address->getVatId(),
                                'address_type' => 'shipping',
                                'customer_address_id' => $addressId,
                                'street' => $address->getStreet(),
                            ];
                        } catch (\Exception $e) {
                            $error = __('Invalid Shipping Address Provided.');
                        }
                        if (isset($address) && $address && $address->getRegion() && !$error) {
                            $addressData['region'] = $address->getRegion()->getRegion();
                        }
                    } else {
                        $addressData = $params['shipping_address'];
                        $addressData['street'] = [
                            $addressData['street0'],
                            $addressData['street1'],
                            isset($addressData['street2']) ? $addressData['street2'] : ''
                        ];
                        $addressData['region'] = $params['region'];
                        $addressData['address_type'] = 'shipping';
                    }
                    $profile->getShippingAddress()->setData($addressData);
                    $renderer = $this->addressConfig->getFormatByCode('oneline')->getRenderer();
                    $addressLine = $renderer->renderArray($addressData);
                }
                try {
                    $tempQuote = $this->profileManager->getTempQuote($profile);
                } catch (\Exception $e) {
                    $tempQuote = null;
                }
                if ($tempQuote) {
                    $shippingRates = $tempQuote->getShippingAddress()->getAllShippingRates();
                } else {
                    $shippingRates = null;
                }
                if ($shippingRates) {
                    foreach ($shippingRates as $rate) {
                        $rates[] = [
                            'value' => $rate->getCode(),
                            'label' => $rate->getCarrierTitle() . ' (' . $rate->getMethodTitle() . ')',
                        ];
                    }
                } else {
                    $error = __('No shipping methods are available.');
                }
            }
        }
        $response = [
            'error' => $error,
            'shipping_methods' => $rates,
            'shipping_address_summary' => $addressLine
        ];

        if ($this->getRequest()->getParam('isAjax', false)) {
            return $this->resultJsonFactory->create()->setJsonData($this->serializer->serialize($response));
        }

        throw new NotFoundException(__('Not found'));
    }
}
