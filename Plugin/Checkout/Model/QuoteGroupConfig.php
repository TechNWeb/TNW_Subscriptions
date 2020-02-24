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
        $result['totalsData']['items'] = $this->modifyTotalsDataItems($result['totalsData']['items']);
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuoteGroupData()
    {
        $quoteGroupData = [];
        $quoteItems = $this->getQuoteItems();
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
     * @param $totalsDataItems
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function modifyTotalsDataItems($totalsDataItems)
    {
        $quoteItems = $this->getQuoteItems();
        foreach ($quoteItems as $quoteItem) {
            if (null !== $quoteItem->getOptionByCode('subscription')) {
                foreach ($totalsDataItems as $key => $totalsDataItem) {
                    if ($totalsDataItem['item_id'] === $quoteItem->getItemId()) {
                        $totalsDataItems[$key]['subscription_price'] =
                            $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($quoteItem);
                            break;
                    }
                }
            }
        }
        return $totalsDataItems;
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
