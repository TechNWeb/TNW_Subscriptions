<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save modified products processor.
 */
class ModifiedProducts extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $saveModel = $this->getSubCreateModel();
        $objectId = $this->getFieldValue($data, 'objectId', false);
        if ($objectId) {
            $objectItemId = $this->getFieldValue($data, 'objectItemId', false);
            if ($objectItemId) {
                $quote = $saveModel->getQuoteCreator()->getCartRepository()->get($objectId);
                $quoteId = $quote->getId();
                $item = $quote->getItemById($objectItemId);
                $remove = $this->getFieldValue($data, 'remove', false);
                $request = $this->getFieldValue($data, 'item_' . $objectItemId, false);
                $request['product_id'] = $item->getProduct()->getId();
                $oldAddressData = $saveModel->getShippingAddress()->getData();
                $oldAddressData['street'] = $saveModel->getShippingAddress()->getStreet();
                unset($oldAddressData['address_id']);
                unset($oldAddressData['quote_id']);
                unset($oldAddressData['entity_id']);
                $saveModel->removeSubscriptions($item);
                if (!$remove) {
                    $result = $saveModel->addToSubscription($request);
                    if (!$result) {
                        $this->errors[] = __('We can\'t add this item to your subscription shopping cart right now.');
                    } else {
                        $result->getQuote()->removeAllAddresses();
                        $this->getSession()->addSubQuote($result->getQuote());
                        $customerAddressId = array_key_exists('customer_address_id', $oldAddressData)
                            ? $oldAddressData['customer_address_id']
                            : null;
                        $saveModel->setShippingAddress($oldAddressData, $customerAddressId);
                        $saveModel->getQuoteCreator()->getCartRepository()->save($result->getQuote());
                    }
                } else {
                    try {
                        $saveModel->getQuoteCreator()->getCartRepository()->get($quoteId);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        //remove quote from session if quote does not exist.
                        $this->getSession()->removeSubQuote($quoteId);
                    }
                }
            } else {
                $this->errors[] = __('Object item id is not defined.');
            }
        } else {
            $this->errors[] = __('Object id is not defined.');
        }
    }
}
