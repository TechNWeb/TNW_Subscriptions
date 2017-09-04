<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Review;

use Magento\Backend\Block\Template;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Block for render shipping and billing addresses on subscription profile review page.
 */
class Address extends Template
{
    /**
     * Help retrieve address by type(Shipping, Billing).
     *
     * @var Create
     */
    private $create;

    /**
     * Address type holder.
     *
     * @var string
     */
    private $addressType;

    /**
     * Help retrieve address format by code.
     *
     * @var Config
     */
    private $addressConfig;

    /**
     * @var string
     */
    private $profileState;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Address constructor.
     *
     * @param Template\Context $context
     * @param Create $create
     * @param Config $addressConfig
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param RequestInterface $request
     * @param string $addressType
     * @param $profileState
     * @param array $data
     * @internal param Registry $registry
     */
    public function __construct(
        Template\Context $context,
        Create $create,
        Config $addressConfig,
        SubscriptionProfileRepositoryInterface $profileRepository,
        RequestInterface $request,
        $addressType = QuoteAddress::ADDRESS_TYPE_SHIPPING,
        $profileState,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/address.phtml');
        $this->addressType = $addressType;
        $this->create = $create;
        $this->addressConfig = $addressConfig;
        $this->profileState = $profileState;
        $this->profileRepository = $profileRepository;
        $this->request = $request;

        parent::__construct($context, $data);
    }

    /**
     * Get formatted address depends on address type(Shipping, Billing)
     * and on subscription profile state (create, edit).
     *
     * @return string
     */
    public function getAddress()
    {
        $formattedAddress = '';
        switch ($this->profileState) {
            case SubscriptionProfile::STATE_CREATE :
                $formattedAddress = $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
                    ? $this->formatQuoteAddress($this->create->getShippingAddress())
                    : $this->formatQuoteAddress($this->create->getBillingAddress());
                break;
            case SubscriptionProfile::STATE_EDIT :
                /** @var SubscriptionProfile $profile */
                $profile = $this->getProfile();
                if ($profile) {
                    $formattedAddress = $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
                        ? $this->formatProfileAddress($profile->getShippingAddress())
                        : $this->formatProfileAddress($profile->getBillingAddress());
                }
                break;
        }
        return $formattedAddress;
    }

    /**
     * Returns current subscription profile from request param.
     *
     * @return SubscriptionProfile|null
     */
    private function getProfile()
    {
        $profileId = (int)$this->request->getParam(SummaryInsertForm::FORM_DATA_KEY, 0);
        try {
            /** @var SubscriptionProfile $profile */
            $profile = $this->profileRepository->getById($profileId);
        } catch (\Exception $e) {
            $profile = null;
        }

        return $profile;
    }

    /**
     * Get address in proper string format for render.
     *
     * @param QuoteAddress $address
     * @return string
     */
    private function formatQuoteAddress(QuoteAddress $address)
    {
        $formatType = $this->addressConfig->getFormatByCode('html');

        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * Get address in proper string format for render.
     *
     * @param SubscriptionProfileAddressInterface $address
     * @return string
     */
    private function formatProfileAddress(SubscriptionProfileAddressInterface $address)
    {
        $formatType = $this->addressConfig->getFormatByCode('html');

        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * @return string
     */
    public function getAddressType()
    {
        return $this->addressType;
    }
}
