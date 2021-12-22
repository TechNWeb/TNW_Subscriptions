<?php

namespace TNW\Subscriptions\Model\Customer\Attribute\Source;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Customer\Attribute\Source\Group;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\Convert\DataObject;
use TNW\Subscriptions\Model\Config;

/**
 * Customer group attribute source
 */
class CustomerGroup extends Group
{
    /**
     * @var Config
     */
    private $subscriptionConfig;

    /**
     * CustomerGroup constructor.
     * @param CollectionFactory $attrOptionCollectionFactory
     * @param OptionFactory $attrOptionFactory
     * @param GroupManagementInterface $groupManagement
     * @param DataObject $converter
     * @param Config $subscriptionConfig
     */
    public function __construct(
        CollectionFactory $attrOptionCollectionFactory,
        OptionFactory $attrOptionFactory,
        GroupManagementInterface $groupManagement,
        DataObject $converter,
        Config $subscriptionConfig
    ) {
        $this->subscriptionConfig = $subscriptionConfig;
        parent::__construct(
            $attrOptionCollectionFactory,
            $attrOptionFactory,
            $groupManagement,
            $converter
        );
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $result = [];
        if (!$this->_options) {
            $groups = $this->_groupManagement->getLoggedInGroups();

            $this->_options = $this->_converter->toOptionArray($groups, 'id', 'code');
            if ($this->subscriptionConfig->getAllowAllCustomerGroups()) {
                $customerGroups = $this->subscriptionConfig->getCustomerGroupLimit();
                if ($customerGroups != null) {
                    $customerGroupArray = explode(',', $customerGroups);
                    foreach ($this->_options as $option) {
                        if (array_search($option['value'], $customerGroupArray) === false) {
                            $result[] = [
                                'value' => $option['value'],
                                'label' => $option['label'],
                                'disabled' => true
                            ];
                        } else {
                            $result[] = [
                                'value' => $option['value'],
                                'label' => $option['label'],
                                'disabled' => false
                            ];
                        }
                    }
                }
            }
        }
        return $result;
    }
}
