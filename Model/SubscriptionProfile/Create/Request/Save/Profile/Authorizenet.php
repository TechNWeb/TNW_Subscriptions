<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Quote\Model\Quote;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use net\authorize\api\contract\v1\ANetApiResponseType;
use net\authorize\api\contract\v1\CreateCustomerPaymentProfileResponse;
use net\authorize\api\contract\v1\CustomerPaymentProfileMaskedType;
use net\authorize\api\contract\v1\GetCustomerProfileResponse;
use TNW\AuthorizeCim\Gateway\Http\Client\CreateCustomerPaymentProfile;
use TNW\AuthorizeCim\Gateway\Http\Client\GetCustomerProfile;
use TNW\AuthorizeCim\Gateway\Http\TransferFactory;
use TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Class Authorizenet - used as save process for authorizenet payment
 */
class Authorizenet extends Base
{
    /**
     * @var string[]
     */
    public static $creditCardAbbreviations = [
        'AE' => 'American Express',
        'VI' => 'Visa',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'JBC' => 'JBC',
        'DN' => 'Diners Club',
    ];

    /**
     * @var VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var GetCustomerProfile
     */
    private $getCustomerProfileClient;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var CreateCustomerPaymentProfile
     */
    private $createCustomerPaymentProfileClient;

    /**
     * Authorizenet constructor.
     * @param CreateProfile $createModel
     * @param QuoteSessionInterface $session
     * @param VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param EncryptorInterface $encryptor
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     * @param ObjectManagerInterface $objectManager
     * @param Manager $moduleManager
     */
    public function __construct(
        CreateProfile $createModel,
        QuoteSessionInterface $session,
        VaultPaymentAuthorization $vaultPaymentAuthorization,
        EncryptorInterface $encryptor,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $tokenManagement,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        ObjectManagerInterface $objectManager,
        Manager $moduleManager
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->paymentTokenManagement = $tokenManagement;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->jsonSerializer = $jsonSerializer;
        if ($moduleManager->isEnabled('TNW_AuthorizeCim')) {
            $this->transferFactory = $objectManager->get(TransferFactory::class);
            $this->getCustomerProfileClient = $objectManager->get(GetCustomerProfile::class);
            $this->createCustomerPaymentProfileClient = $objectManager->get(CreateCustomerPaymentProfile::class);
        }
        parent::__construct($createModel, $session);
    }

    /**
     * @param array $data
     * @throws LocalizedException
     * @throws CommandException
     */
    public function process(array $data)
    {
        if (empty($data['payment']['tnw_authorize_cim']['method'])) {
            return;
        }
        $paymentData = $data['payment']['tnw_authorize_cim'];
        $paymentData['method'] = 'tnw_authorize_cim';
        $paymentData['additional_data'] = array_merge($paymentData, $paymentData['additional']);

        /** @var Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();
        $quote = reset($subQuotes);
        $guestEmail = null;
        if (!$quote->getCustomerId()) {
            $guestEmail = $this->getSession()->getCustomerEmail();
        }
        $customer = $quote->getCustomer();
        if ($customer && ($customerProfileAttr = $customer->getCustomAttribute('customer_profile_id'))
            && $customerProfileAttr->getValue()) {
            $paymentToken = $this->getPaymentTokenForAuthNetCustomer($customer, $quote, $paymentData);
        } else {
            $result = $this->vaultPaymentAuthorization->processPreAuthForTrial($paymentData, $quote, $guestEmail);
            $paymentToken = $result['payment_token'];
        }
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setPaymentMethodCode('tnw_authorize_cim');
        $this->paymentTokenRepository->save($paymentToken);
        /** @var Quote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $subQuote->getPayment()
                ->setAdditionalInformation('cc_number', $paymentData['cc_last_4'])
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setMethod('tnw_authorize_cim_vault')
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($paymentData['additional']['cc_type'])
                ->setCcLast4($paymentData['cc_last_4'])
                ->setCcExpMonth($paymentData['additional']['cc_exp_month'])
                ->setCcExpYear($paymentData['additional']['cc_exp_year']);
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * Returns payment token for customer payment profile. Creates profile or token if necessary.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @param Quote $quote
     * @param array $paymentData
     * @return PaymentTokenInterface
     * @throws ClientException
     */
    private function getPaymentTokenForAuthNetCustomer($customer, Quote $quote, array $paymentData)
    {
        $customerProfile = $this->getCustomerProfile($customer);
        $paymentProfile = $this->findMatchingPaymentProfile($customerProfile, $quote, $paymentData);
        if ($paymentProfile) {
            $paymentToken = $this->findPaymentTokenForPaymentProfile($customer, $paymentProfile);
            if ($paymentToken) {
                return $paymentToken;
            } else {
                $maskedCC = $paymentProfile->getPayment()->getCreditCard()->getCardNumber();
                return $this->createPaymentToken(
                    $customer,
                    $paymentProfile->getCustomerPaymentProfileId(),
                    $maskedCC,
                    $paymentData
                );
            }
        } else {
            $createPaymentProfileResponse = $this->createCustomerPaymentProfile($customer, $quote, $paymentData);
            $maskedCC = $paymentData['cc_last_4'];
            return $this->createPaymentToken(
                $customer,
                $createPaymentProfileResponse->getCustomerPaymentProfileId(),
                $maskedCC,
                $paymentData
            );
        }
    }

    /**
     * Returns Authorize.net customer payment profile.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @return GetCustomerProfileResponse
     * @throws ClientException
     * @throws LocalizedException
     */
    private function getCustomerProfile($customer)
    {
        $transferObject = $this->transferFactory->create([
            'customer_profile_id' => $customer->getCustomAttribute('customer_profile_id')->getValue(),
            'unmask_expiration_date' => true
        ]);
        $customerProfileResponse = $this->getCustomerProfileClient->placeRequest($transferObject);
        $this->processAuthNetErrors($customerProfileResponse['object']);
        return $customerProfileResponse['object'];
    }

    /**
     * Creates customer payment profile on Authorize.net.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @param Quote $quote
     * @param array $paymentData
     * @return CreateCustomerPaymentProfileResponse
     * @throws ClientException
     * @throws LocalizedException
     */
    private function createCustomerPaymentProfile($customer, Quote $quote, array $paymentData)
    {
        $requestData = $this->buildPaymentProfileCreateRequestData($customer, $quote, $paymentData);
        $transferObject = $this->transferFactory->create($requestData);
        $response = $this->createCustomerPaymentProfileClient->placeRequest($transferObject);
        $this->processAuthNetErrors($response['object']);
        return $response['object'];
    }

    /**
     * Finds matching payment profile for given credit card and billing address.
     *
     * @param GetCustomerProfileResponse $profileResponse
     * @param Quote $quote
     * @param array $paymentData
     * @return CustomerPaymentProfileMaskedType|null
     */
    private function findMatchingPaymentProfile(
        GetCustomerProfileResponse $profileResponse,
        Quote $quote,
        array $paymentData
    ) {
        if (!$profileResponse->getProfile() || !$profileResponse->getProfile()->getPaymentProfiles()) {
            return null;
        }

        $matched = array_filter(
            $profileResponse->getProfile()->getPaymentProfiles(),
            function ($paymentProfile) use ($paymentData) {
                $creditCard = $paymentProfile->getPayment()->getCreditCard();
                $expDate = sprintf(
                    '%d-%02d',
                    $paymentData['additional']['cc_exp_year'],
                    $paymentData['additional']['cc_exp_month']
                );
                return substr($creditCard->getCardNumber(), 4, 4) === $paymentData['cc_last_4'] &&
                    $creditCard->getExpirationDate() === $expDate &&
                    $creditCard->getCardType() === $this->getCreditCardType($paymentData['additional']['cc_type']);
            }
        );

        $billingAddress = $quote->getBillingAddress();

        if ($billingAddress) {
            $matched = array_filter(
                $matched,
                function ($paymentProfile) use ($billingAddress) {
                    $billTo = $paymentProfile->getBillTo();
                    return $billTo->getFirstName() == $billingAddress->getFirstname() &&
                        $billTo->getLastName() == $billingAddress->getLastname() &&
                        $billTo->getCompany() == $billingAddress->getCompany() &&
                        $billTo->getAddress() == $billingAddress->getStreetLine(1) &&
                        $billTo->getCity() == $billingAddress->getCity() &&
                        $billTo->getState() == $billingAddress->getRegionCode() &&
                        $billTo->getZip() == $billingAddress->getPostcode() &&
                        $billTo->getCountry() == $billingAddress->getCountryId() &&
                        $billTo->getPhoneNumber() == $billingAddress->getTelephone();
                }
            );
        }

        if (count($matched)) {
            return reset($matched);
        }

        return null;
    }

    /**
     * Returns credit card type by abbreviation.
     *
     * @param string $abbreviation
     * @return string|null
     */
    private function getCreditCardType($abbreviation)
    {
        return self::$creditCardAbbreviations[$abbreviation] ?? null;
    }

    /**
     * Finds existing payment token for given payment profile.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @param CustomerPaymentProfileMaskedType $paymentProfile
     * @return PaymentTokenInterface|null
     */
    private function findPaymentTokenForPaymentProfile($customer, CustomerPaymentProfileMaskedType $paymentProfile)
    {
        $gatewayToken = sprintf(
            '%s/%s',
            $customer->getCustomAttribute('customer_profile_id')->getValue(),
            $paymentProfile->getCustomerPaymentProfileId()
        );

        return $this->paymentTokenManagement
            ->getByGatewayToken($gatewayToken, 'tnw_authorize_cim', $customer->getId());
    }

    /**
     * Creates payment token.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @param int|string $paymentProfileId
     * @param string $maskedCC
     * @param array $paymentData
     * @return PaymentTokenInterface
     */
    private function createPaymentToken($customer, $paymentProfileId, $maskedCC, $paymentData)
    {
        $paymentToken = $this->paymentTokenFactory->create('card');
        $time = sprintf(
            '%s-%s-01 00:00:00',
            trim($paymentData['additional_data']['cc_exp_year']),
            trim($paymentData['additional_data']['cc_exp_month'])
        );
        $expirationDate = date_create($time, timezone_open('UTC'))
            ->modify('+1 month')
            ->format('Y-m-d 00:00:00');
        $gatewayToken = sprintf(
            '%s/%s',
            $customer->getCustomAttribute('customer_profile_id')->getValue(),
            $paymentProfileId
        );
        $tokenDetails = $this->jsonSerializer->serialize([
            'type' => $paymentData['additional_data']['cc_type'],
            'maskedCC' => str_replace('XXXX', '', $maskedCC),
            'expirationDate' => sprintf(
                '%s/%s',
                $paymentData['additional_data']['cc_exp_month'],
                $paymentData['additional_data']['cc_exp_year']
            )
        ]);
        $paymentToken->setExpiresAt($expirationDate)
            ->setGatewayToken($gatewayToken)
            ->setTokenDetails($tokenDetails ?: '{}');
        return $paymentToken;
    }

    /**
     * Builds request data array for customer payment profile create request.
     *
     * @param CustomerInterface|ExtensibleDataInterface $customer
     * @param Quote $quote
     * @param array $paymentData
     * @return array
     */
    private function buildPaymentProfileCreateRequestData($customer, Quote $quote, array $paymentData)
    {
        $billingAddress = $quote->getBillingAddress();
        $data = [
            'customer_profile_id' => $customer->getCustomAttribute('customer_profile_id')->getValue(),
            'paymentProfile' => [
                'payment' => [
                    'opaque_data' => [
                        'data_descriptor' => $paymentData['opaqueDescriptor'],
                        'data_value' => $paymentData['opaqueValue']
                    ]
                ],
                'bill_to' => [
                    'first_name' => $billingAddress->getFirstname(),
                    'last_name' => $billingAddress->getLastname(),
                    'company' => $billingAddress->getCompany(),
                    'address' => $billingAddress->getStreetLine(1),
                    'city' => $billingAddress->getCity(),
                    'state' => $billingAddress->getRegionCode(),
                    'zip' => $billingAddress->getPostcode(),
                    'country' => $billingAddress->getCountryId(),
                    'phone_number' => $billingAddress->getTelephone()
                ]
            ]
        ];

        return $data;
    }

    /**
     * Processes error messages from Authorize.net API responses.
     *
     * @param ANetApiResponseType $response
     * @return void
     * @throws LocalizedException
     */
    private function processAuthNetErrors(ANetApiResponseType $response)
    {
        $messages = $response->getMessages();
        if (strcasecmp($messages->getResultCode(), 'Ok') !== 0) {
            $errorMessages = array_map(function ($message) {
                return sprintf('%s: %s', $message->getCode(), $message->getText());
            }, $messages->getMessage());

            throw new LocalizedException(
                __(implode(PHP_EOL, $errorMessages))
            );
        }
    }
}
