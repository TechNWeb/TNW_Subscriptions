<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron\Quote;

use Magento\Framework\Api\SearchCriteria;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface as ProfileRelation;

/**
 * Class Updater
 */
class Updater extends Base
{
    /**
     * @inheritdoc
     */
    public function getProfilesIdsToProcess($websiteId)
    {
        $collection = $this->getBaseCollection()
            ->addFieldToFilter(SubscriptionProfileInterface::WEBSITE_ID, $websiteId)
            ->addFieldToFilter(SubscriptionProfileInterface::STATUS, ['nin' => [
                \TNW\Subscriptions\Model\Source\ProfileStatus::STATUS_COMPLETE,
                \TNW\Subscriptions\Model\Source\ProfileStatus::STATUS_CANCELED
            ]])
            ->addFieldToFilter([
                ['attribute' => SubscriptionProfileInterface::NEED_RECOLLECT, 'eq' => 1],
                ['attribute' => 'products_need_recollect', 'eq' => 1]
            ]);

        return $collection->getAllIds();
    }

    /**
     * Recalculates profile quotes if profile or profile product was changed.
     *
     * @param array $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $data)
    {
        $this->context->messageDebug('Process quote updater');
        foreach ($data as $websiteId) {
            foreach ($this->getProfiles($websiteId) as $profile) {
                $this->context->messageDebug("Update Quotes by Profile:\n%s", $profile);
                try {
                    foreach ($this->getProfileQuotes($profile) as $quoteId) {
                        /** @var \Magento\Quote\Model\Quote $profileQuote */
                        $profileQuote = $this->cartRepository->get($quoteId);

                        $this->context->messageDebug(
                            "Quote. Data Quote:\n%s\nData Quote Items:\n%s",
                            $profileQuote,
                            $profileQuote->getItemsCollection()
                        );

                        // Clear
                        $this->prepareQuote($profileQuote);
                        $this->cartRepository->save($profileQuote);

                        // Fill
                        $profileQuote = $this->cartRepository->get($quoteId);
                        $this->processQuote($profile, $profileQuote);

                        $this->context->messageDebug(
                            "Updated Quote. Data Quote:\n%s\nData Quote Items:\n%s",
                            $profileQuote,
                            $profileQuote->getItemsCollection()
                        );
                    }

                    $this->updateProfileCollectFlag($profile, false);
                } catch (\Exception $e) {
                    $this->context->messageError(
                        'Error on quotes recalculation for profile - %d. Message: %s',
                        $profile->getId(),
                        $e
                    );
                }

                $this->profileRepository->save($profile);
            }
        }
    }

    /**
     * Returns future profile quotes.
     *
     * @param SubscriptionProfileInterface $profile
     * @return int[]
     */
    private function getProfileQuotes(SubscriptionProfileInterface $profile)
    {
        $relations = $this->relationManager->getNextProfileRelation($profile, true);
        return array_map([$this, 'quoteIdByProfileRelation'], $relations ?: []);
    }

    /**
     * @param ProfileRelation $relation
     *
     * @return null|string
     */
    private function quoteIdByProfileRelation(ProfileRelation $relation)
    {
        return $relation->getMagentoQuoteId();
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @param $value
     * @return void
     */
    private function updateProfileCollectFlag(SubscriptionProfileInterface $profile, $value)
    {
        foreach ($profile->getProducts() as $product){
            $product->setNeedRecollect($value);
        }
        $profile->setNeedRecollect($value);
    }

    /**
     * Clears profile quote.
     *
     * @param Quote $profileQuote
     * @return void
     */
    private function prepareQuote($profileQuote)
    {
        $profileQuote->removeAllAddresses();
        $profileQuote->removeAllItems();
        $profileQuote->removePayment();
    }


    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return [];
    }
}
