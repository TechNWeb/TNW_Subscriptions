<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Model\Product as MagentoProduct;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Modal form for editing product options on edit subscription page.
 */
class EditSubscriptionProductOptions extends EditProductOptions
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'edit_modal_edit_product_options_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM = 'tnw_subscriptionprofile_edit_modal_edit_product_options_form';
    /**#@-*/
}
