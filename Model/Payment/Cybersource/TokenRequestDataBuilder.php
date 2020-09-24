<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Cybersource;

/**
 * Class TokenRequestDataBuilder - builds request data for token request
 */
class TokenRequestDataBuilder
{
    /**
     * Token type param name
     */
    const TYPE_CREATE_TOKEN = 'create_payment_token';

    /**
     * Quote Id param name
     */
    const KEY_QUOTE_ID = 'merchant_secure_data1';

    /**
     * Merchant additional secure data  param name
     */
    const KEY_SID = 'merchant_secure_data2';

    /**
     * Agreements ids param name
     */
    const KEY_AGREEMENT_IDS = 'merchant_defined_data12';

    /**
     * Skip decision manager param name
     */
    const KEY_TOKEN_SKIP_DM = 'token_skip_decision_manager';

    /**
     * @var mixed
     */
    private $gatewayConfig;

    /**
     * @var mixed
     */
    private $requestDataBuilder;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $localeResolver;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * TokenRequestDataBuilder constructor.
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->gatewayConfig = $objectManager->get("CyberSource\SecureAcceptance\Gateway\Config\Config");
            $this->requestDataBuilder = $objectManager
                ->get("CyberSource\SecureAcceptance\Helper\RequestDataBuilder");
        }
        $this->localeResolver = $localeResolver;
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
        $this->random = $random;
        $this->encryptor = $encryptor;
    }

    /**
     * @param $requestData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build($requestData)
    {
        $orderId = $requestData['order_id'];
        $sessionId = $requestData['session_id'];
        $cardType = $requestData['card_type'];
        $currency = $requestData['currency'];
        $billingAddressData = $requestData['billing_address'];

        $billingAddress = new \Magento\Framework\DataObject($billingAddressData);
        $data = [];
        $data['access_key'] = $this->getAccessKey();
        $data['profile_id'] = $this->getProfileId();
        $data['transaction_uuid'] = $this->random->getUniqueHash();
        if ($unsignedFieldNames = $this->getUnsignedFieldNames()) {
            $data['unsigned_field_names'] = $unsignedFieldNames;
        }
        $data['locale'] = $this->requestDataBuilder->getLocale();
        $data['transaction_type'] = self::TYPE_CREATE_TOKEN;
        $data['reference_number'] = 'token_request_' . $orderId;
        $data[self::KEY_QUOTE_ID] = $orderId;
        if ($this->gatewayConfig->isSilent()) {
            $data[self::KEY_SID] = $this->encryptor->encrypt($sessionId);
        }
        $data['amount'] = '0.00';
        $data['currency'] = $currency;
        $data['payment_method'] = 'card';
        $data['bill_to_forename'] = $billingAddress->getFirstname();
        $data['bill_to_surname'] = $billingAddress->getLastname();
        $data['bill_to_email'] = $billingAddress->getEmail();
        $data['bill_to_address_country'] = $billingAddress->getCountryId();
        $data['bill_to_address_city'] = $billingAddress->getCity();
        $data['bill_to_address_state'] = $billingAddress->getRegionCode();
        $data['bill_to_address_line1'] = $billingAddress->getStreetLine1();
        if ($streetLine2 = $billingAddress->getStreetLine2()) {
            $data['bill_to_address_line2'] = $streetLine2;
        }
        $data['bill_to_address_postal_code'] = $billingAddress->getPostcode();

        $data['skip_decision_manager'] = $this->gatewayConfig->getValue(
            self::KEY_TOKEN_SKIP_DM
        ) ? 'true' : 'false';

        $data['skip_auto_auth'] = 'true';

        if (!empty($agreementIds)) {
            $data[self::KEY_AGREEMENT_IDS] = implode(',', $agreementIds);
        }

        $data['override_custom_receipt_page'] = $this->urlBuilder->getUrl(
            'tnw_subscriptions/secureAcceptance/receiveToken',
            ['_secure' => true]
        );
        $data['device_fingerprint_id'] = $sessionId;

        $data['signed_date_time'] = $this->dateTime->gmtDate("Y-m-d\\TH:i:s\\Z");
        $data = $this->filterEmptyValues($data);

        $data['signed_field_names'] = $this->requestDataBuilder->getSignedFields($data);

        $data['signature'] = $this->requestDataBuilder->sign($data, $this->getSecretKey());
        $data['card_type'] = $this->requestDataBuilder->getCardType($cardType);

        if ($data['card_type'] == 'undefined') {
            unset($data['card_type']);
        }

        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    private function filterEmptyValues($data)
    {
        return array_filter($data, function ($value) {
            return !empty($value);
        });
    }

    /**
     * @return mixed
     */
    private function getAccessKey()
    {
        if ($this->gatewayConfig->isSilent()) {
            return $this->gatewayConfig->getSopAccessKey();
        }
        return $this->gatewayConfig->getAuthAccessKey();
    }

    /**
     * @return mixed
     */
    private function getProfileId()
    {
        if ($this->gatewayConfig->isSilent()) {
            return $this->gatewayConfig->getSopProfileId();
        }
        return $this->gatewayConfig->getAuthProfileId();
    }

    /**
     * @return mixed
     */
    private function getSecretKey()
    {
        if ($this->gatewayConfig->isSilent()) {
            return $this->gatewayConfig->getSopSecretKey();
        }
        return $this->gatewayConfig->getAuthSecretKey();
    }

    /**
     * @return string|null
     */
    private function getUnsignedFieldNames()
    {
        if ($this->gatewayConfig->isSilent()) {
            return 'card_type,card_number,card_expiry_date,card_cvn';
        }
        return null;
    }
}
