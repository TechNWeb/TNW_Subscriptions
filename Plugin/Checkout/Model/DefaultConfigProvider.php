<?php

namespace TNW\Subscriptions\Plugin\Checkout\Model;

class DefaultConfigProvider
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

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->quoteItemGroup = $quoteItemGroup;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, $result)
    {
        $result['quoteGroupData'] = $this->getQuoteGroupData();
        return $result;
    }

    private function getQuoteGroupData()
    {
        $quoteGroupData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->quoteItemRepository->getList($quoteId);
            foreach ($this->quoteItemGroup->groups($quoteItems) as $group) {
                $quoteGroupData[] = [
                    'caption' => $this->quoteItemGroup->caption($group),
                    'description' => $this->quoteItemGroup->frequencyDescription($group),
                    'itemIds' => array_map(function ($item) {
                        return $item->getId();
                    }, $group)
                ];
            }
        }

        return $quoteGroupData;
    }
}
