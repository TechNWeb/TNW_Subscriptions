<?php

namespace TNW\Subscriptions\Plugin\Avatax;

use ClassyLlama\AvaTax\Helper\ExtensionAttributeMerger;

class PreventExtensionCopy
{
    /**
     * @param ExtensionAttributeMerger $subject
     * @param callable $proceed
     * @param $extensionAttributes
     * @param $key
     * @param $value
     * @return PreventExtensionCopy
     */
    public function aroundSetExtensionAttribute(
        ExtensionAttributeMerger $subject,
        callable $proceed,
        $extensionAttributes,
        $key,
        $value
    ) {
        if ($key !== 'subs_initial_fees') {
            return $proceed($extensionAttributes, $key, $value);
        }
        return $this;
    }
}
