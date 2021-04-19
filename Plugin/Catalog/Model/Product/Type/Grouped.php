<?php

namespace TNW\Subscriptions\Plugin\Catalog\Model\Product\Type;

use Magento\Framework\DataObject;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGrouped;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\Grouped as TypeManagerGrouped;

class Grouped
{
    /**
     * @var TypeManagerGrouped
     */
    private $grouped;

    /**
     * Grouped constructor.
     * @param TypeManagerGrouped $grouped
     */
    public function __construct(TypeManagerGrouped $grouped)
    {
        $this->grouped = $grouped;
    }

    /**
     * @param TypeGrouped $subject
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     */
    public function aroundPrepareForCartAdvanced(
        TypeGrouped $subject,
        callable $proceed,
        DataObject $buyRequest,
        $product,
        $processMode = null
    ) {
        if (isset($buyRequest['subscribe_active']) && $buyRequest['subscribe_active']) {
            $cartCandidates = [];

            foreach ($this->grouped->getChildProducts($product) as $subProduct) {
                if (empty($buyRequest['subs_group'][$subProduct->getId()])
                    || !isset($buyRequest['subs_group'][$subProduct->getId()]['subscribe_qty'])
                    || $buyRequest['subs_group'][$subProduct->getId()]['subscribe_qty'] === '0'
                ) {
                    continue;
                }
                $subBuyRequest = new DataObject($buyRequest->getData());
                $subBuyRequest->unsetData(['super_group', 'subs_group'])
                    ->setData('item', $subProduct->getId())->setData('product', $subProduct->getId());
                $subBuyRequest->addData($buyRequest['subs_group'][$subProduct->getId()]);
                $candidate = $subProduct->getTypeInstance()
                    ->prepareForCartAdvanced($subBuyRequest, $subProduct);
                if (!empty($candidate[0])) {
                    $cartCandidates[] = $candidate[0];
                }
            }
            return $cartCandidates;
        }
        return $proceed($buyRequest, $product, $processMode);
    }
}
