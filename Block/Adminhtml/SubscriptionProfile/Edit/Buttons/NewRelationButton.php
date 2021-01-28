<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;

/**
 * Class NewRelationButton - button for new schedule creation
 */
class NewRelationButton extends ChangeStatusButton implements ButtonProviderInterface
{
    /**
     * @var BillingCyclesManager
     */
    private $billingCyclesManager;

    /**
     * @var RelationManager
     */
    private $relationManager;

    /**
     * NewRelationButton constructor.
     * @param Context $context
     * @param Registry $registry
     * @param StatusManager $statusManager
     * @param BillingCyclesManager $billingCyclesManager
     * @param RelationManager $relationManager
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StatusManager $statusManager,
        BillingCyclesManager $billingCyclesManager,
        RelationManager $relationManager
    ) {
        $this->relationManager = $relationManager;
        $this->billingCyclesManager = $billingCyclesManager;
        parent::__construct($context, $registry, $statusManager);
    }

    /**
     * Retrieves schedule button data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->canSchedule()) {
            return [];
        }
        return [
            'label' => __('Schedule Next Payment'),
            'on_click' => sprintf("location.href = '%s';", $this->getScheduleUrl()),
            'class' => 'cancel',
            'sort_order' => 15
        ];
    }

    /**
     * Get URL for schedule button
     *
     * @return string
     */
    public function getScheduleUrl()
    {
        return $this->getUrl('*/*/schedule', ['profile_id' => $this->getCurrentSubscriptionProfile()->getId()]);
    }

    /**
     * {inheritdoc}
     */
    protected function getStatus()
    {
        return ProfileStatus::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    private function canSchedule()
    {
        $result = false;
        $profile = $this->getCurrentSubscriptionProfile();
        if ($profile->getStatus() == ProfileStatus::STATUS_ACTIVE
            && !$this->relationManager->getNextProfileRelation($profile)
        ) {
            $billingCyclesManager = $this->billingCyclesManager;
            try {
                list($cycles, $needMore, $existingCycles) =
                    $billingCyclesManager->getBillingCycles($profile, 1, true);
            } catch (\Exception $e) {
                $needMore = false;
                $cycles = [];
            }
            if ($needMore && $cycles) {
                $result = true;
            }
        }
        return $result;
    }
}
