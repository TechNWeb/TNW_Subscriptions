<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\BillingFrequency;

use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class Save - save billing frequency action
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var \TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface
     */
    private $billingFrequencyRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \TNW\Subscriptions\Model\BillingFrequencyFactory
     */
    protected $billingFrequencyFactory;

    /**
     * @var \TNW\Subscriptions\Model\BillingFrequencyManager
     */
    private $billingFrequencyManager;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param \TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \TNW\Subscriptions\Model\BillingFrequencyFactory $billingFrequencyFactory
     * @param \TNW\Subscriptions\Model\BillingFrequencyManager $billingFrequencyManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        \TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \TNW\Subscriptions\Model\BillingFrequencyFactory $billingFrequencyFactory,
        \TNW\Subscriptions\Model\BillingFrequencyManager $billingFrequencyManager
    ) {
        $this->billingFrequencyFactory = $billingFrequencyFactory;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->dataPersistor = $dataPersistor;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->billingFrequencyManager = $billingFrequencyManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     * |\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $data['links']['linked'] = json_decode($data['links']['linked'], true);
        $data['linked_product_listing'] = json_decode($data['linked_product_listing'], true);
        if ($data) {
            $id = $this->getRequest()->getParam('id');

            if ($id) {
                $model = $this->billingFrequencyRepository->getById($id);
                if (!$model->getId() && $id) {
                    $this->messageManager->addErrorMessage(__('This Billing Frequency no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
                $canBeModified = true;
                foreach ($model->getData() as $dataKey => $storedDataValue) {
                    if (isset($data[$dataKey])
                        && $data[$dataKey] != $storedDataValue
                        && !$model->isAllowedModification($dataKey)
                    ) {
                        $canBeModified = false;
                        break;
                    }
                }
                if (!$canBeModified) {
                    $filterByProductId = $this->filterBuilder
                        ->setField(
                            \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID
                        )
                        ->setConditionType('eq')
                        ->setValue($id)
                        ->create();
                    $filterByState = $this->filterBuilder
                        ->setField('status')
                        ->setConditionType('nin')
                        ->setValue([
                            ProfileStatus::STATUS_CANCELED,
                            ProfileStatus::STATUS_COMPLETE
                        ])
                        ->create();
                    $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filterByProductId, $filterByState])
                        ->create();
                    $activeSubscriptionProfiles = $this->subscriptionProfileRepository->getList($searchCriteria)
                        ->getItems();
                    if (count($activeSubscriptionProfiles) > 1) {
                        $this->messageManager->addErrorMessage(
                            __('Cannot change billing frequency. There are active subscription Profiles.')
                        );
                        return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()
                            ->getParam('id')]);
                    }
                }
            } else {
                $model = $this->billingFrequencyFactory->create();
            }

            $model->setData($data);

            try {
                $this->billingFrequencyManager->saveBillingFrequency($model);
                $this->messageManager->addSuccessMessage(__('You saved the Billing Frequency.'));
                $this->dataPersistor->clear('tnw_subscriptions_billingfrequency');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while saving the Billing Frequency.')
                );
            }

            $this->dataPersistor->set('tnw_subscriptions_billingfrequency', $data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::BillingFrequency_save'
        );
    }
}
