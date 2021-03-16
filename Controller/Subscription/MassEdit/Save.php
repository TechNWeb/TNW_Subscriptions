<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\MassEdit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\Pool as SaveProcessorsPool;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Block\Subscription\Billing\DetailsView;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Payment\Gateway\Command\CommandException;
use TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile\Payment as PaymentProcessor;

/**
 * Class Save - process update profiles data via wizard
 */
class Save extends Action
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
     * @var SaveProcessorsPool
     */
    private $saveProcessorPool;

    /**
     * @var CreateProfile
     */
    private $createProfile;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * Save constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SerializerInterface $serializer
     * @param SaveProcessorsPool $saveProcessorPool
     * @param CreateProfile $createProfile
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param Manager $profileManager
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PageFactory $resultPageFactory
     * @param AddressConfig $addressConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SerializerInterface $serializer,
        SaveProcessorsPool $saveProcessorPool,
        CreateProfile $createProfile,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        Manager $profileManager,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PageFactory $resultPageFactory,
        AddressConfig $addressConfig
    ) {
        parent::__construct($context);
        $this->addressConfig = $addressConfig;
        $this->resultPageFactory = $resultPageFactory;
        $this->profileManager = $profileManager;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->saveProcessorPool = $saveProcessorPool;
        $this->createProfile = $createProfile;
        $this->filterBuilder= $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $messages = [];
        $error = false;
        $profile = null;
        $profile = null;
        $profiles = null;

        $data = $this->getRequest()->getParams();
        $updateProfiles = (array_key_exists('payment_set', $data) && $data['payment_set'] == "false");
        $this->modifyRequestData($data);
        if (array_key_exists('selectedSubsIds', $data) && is_array($data['selectedSubsIds'])) {
            $filter = $this->filterBuilder
                ->setField('entity_id')
                ->setConditionType('in')
                ->setValue([$data['selectedSubsIds']])
                ->create();

            $this->searchCriteriaBuilder->addFilters([$filter]);
            try {
                $profiles = $this->subscriptionProfileRepository
                    ->getList($this->searchCriteriaBuilder->create())
                    ->getItems();
                $profile = reset($profiles);
            } catch (\Exception $e) {
                $messages = [__('Invalid Subscription Ids Provided.')];
                $error = true;
                $profiles = [];
            }
        }
        if ($profile) {
            try {
                $tempQuote = $this->profileManager->getTempQuote($profile);
                $this->createProfile->setSubQuotes([$tempQuote]);
            } catch (\Exception $e) {
                $messages = [__('Invalid Data Provided.')];
                $error = true;
            }
        }
        $passedProfile = null;
        foreach ($profiles as $profile) {
            try {
                $instances =  $this->saveProcessorPool->getProcessorsInstances();
                $this->profileManager->reset();
                $this->profileManager->setProfile($profile);
                foreach ($instances as $processor) {
                    if ($processor instanceof PaymentProcessor
                        && array_key_exists('summary', $data)
                        && is_array($data['summary'])
                        && array_key_exists('payment_info', $data['summary'])
                    ) {
                        $this->populateProcessedPaymentData(
                            $profile,
                            json_decode($data['summary']['payment_info'], true)
                        );
                    } else {
                        $processor->process($data);
                    }
                    $messages = array_merge($messages, $processor->getErrors());
                }
                $passedProfile = $profile;
            } catch (CommandException $e) {
                if ($passedProfile) {
                    $originProfileId = $profile->getPayment()->getId();
                    $profile->setPayment(
                        $passedProfile
                            ->getPayment()
                            ->setSubscriptionProfile($profile)
                            ->setProfileId($profile->getId())
                            ->setId($originProfileId)
                    );
                } else {
                    $messages = array_merge($messages, [$e->getMessage()]);
                    $error = true;
                }

            } catch (\Exception $e) {
                $messages = array_merge($messages, [$e->getMessage()]);
                $error = true;
            }
            if (!$error && !$updateProfiles) {
                break;
            }
        }
        $paymentDetailsBlock = $this->resultPageFactory->create()->getLayout()->createBlock(
            DetailsView::class,
            'payment.details',
            [
                'show_title' => true,
            ]
        )->setSubscriptionProfile($profile)->toHtml();

        if ($updateProfiles) {
            if (!$profiles) {
                array_merge($messages, [__('Invalid Profile Ids provided')]);
            }
            foreach ($profiles as $profile) {
                $this->profileManager->reset();
                try {
                    $this->profileManager->setProfile($profile);
                    $this->profileManager->saveProfile();
                } catch (\Exception $e) {
                    $messages = array_merge($messages, [__('Profile %1 could not be updated.', $profile->getId())]);
                    $error = true;
                }
            }
            $messages = array_merge($messages, [__('Profiles updated.')]);
        } else {
            $messages = array_merge($messages, [__('Payment Info Updated')]);
        }
        $response = [
            'mass_edit_result' =>
                [
                    'message' => implode(' ', $messages),
                    'error' => $error ? implode(' ', $messages) : false
                ],
            'error' => $error ? implode(' ', $messages) : $error,
            'billing_address_summary' => $this->addressConfig
                ->getFormatByCode('oneline')
                ->getRenderer()
                ->renderArray($profile->getBillingAddress()->getData()),
            'payment_summary' => $paymentDetailsBlock,
            'payment' => json_encode([
                'method' => $passedProfile->getPayment()->getEngineCode(),
                'token' => $passedProfile->getPayment()->getPaymentToken(),
                'additional_info' => $passedProfile->getPayment()->getDecodedPaymentAdditionalInfo()
            ])
        ];
        return $this->resultJsonFactory->create()->setJsonData($this->serializer->serialize($response));
    }

    /**
     * @param $profile
     * @param $paymentData
     * @return $this
     */
    private function populateProcessedPaymentData($profile, $paymentData)
    {
        $profile->getPayment()->setEngineCode($paymentData['method']);
        if ($paymentData['additional_info']) {
            $profile->getPayment()->setEncodedPaymentAdditionalInfo($paymentData['additional_info']);
            $profile->getPayment()->setPaymentToken($paymentData['token']);
        } else {
            $profile->getPayment()->setPaymentAdditionalInfo('');
            $profile->getPayment()->setTokenHash('');
        }
        return $this;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function modifyRequestData(&$data)
    {
        $data['shipping_info'] = ['mass_update' => true];
        $data['billing_info'] = ['mass_update' => true];
        if (array_key_exists('billing_address', $data)
            && array_key_exists('same_as_shipping', $data['billing_address'])
            && $data['billing_address']['same_as_shipping'] == '1'
        ) {
            $data['billing_address'] = $data['shipping_address'];
            if (array_key_exists('shipping_address_id',$data['shipping_address'])) {
                $data['billing_address']['customer_billing_address_id']
                    = $data['shipping_address']['shipping_address_id'];
            }
        } else {
            $data['billing_address']['customer_billing_address_id'] = $data['billing_address']['billing_address_id'];
        }
        if (array_key_exists('shipping_address_id',$data['shipping_address'])) {
            $data['shipping_address']['customer_shipping_address_id']
                = $data['shipping_address']['shipping_address_id'];
        }

        return $data;
    }
}
