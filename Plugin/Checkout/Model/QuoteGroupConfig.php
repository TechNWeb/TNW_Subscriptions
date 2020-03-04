<?php

namespace TNW\Subscriptions\Plugin\Checkout\Model;

class QuoteGroupConfig
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    private $quoteItemRepository;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * QuoteGroupConfig constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository
     * @param \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup
     * @param \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->descriptionCreator = $descriptionCreator;
    }

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, $result)
    {
        $result['quoteGroupData'] = $this->getQuoteGroupData();
        return $result;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $result['quoteGroupData'] = $this->getQuoteGroupData();
        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Cart\Totals\ItemConverter $subject
     * @param callable $proceed
     * @param $quoteItem
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    public function aroundModelToDataObject(
        \Magento\Quote\Model\Cart\Totals\ItemConverter $subject,
        callable $proceed,
        $quoteItem
    ) {
        $totalsItem = $proceed($quoteItem);
        if (null !== $quoteItem->getOptionByCode('subscription')) {
            $totalsItem->getExtensionAttributes()->setTnwSubscriptionPrice(
                $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($quoteItem));
        }
        return $totalsItem;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuoteGroupData()
    {
        $quoteItems = $this->getQuoteItems();
        if (!$quoteItems) {
            return [];
        }
        $quoteGroupData = [];
        foreach ($this->quoteItemGroup->groups($quoteItems) as $group) {
            $quoteGroupData[] = [
                'caption' => $this->quoteItemGroup->caption($group),
                'description' => $this->quoteItemGroup->frequencyDescription($group),
                'itemIds' => array_map(function ($item) {
                    return $item->getId();
                }, $group)
            ];
        }
        return $quoteGroupData;
    }

    /**
     * @return bool|\Magento\Quote\Api\Data\CartItemInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuoteItems()
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            return $this->quoteItemRepository->getList($quoteId);
        }
        return false;
    }
}
