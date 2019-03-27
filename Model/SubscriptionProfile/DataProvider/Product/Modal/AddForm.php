<?php
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

class AddForm extends Form
{
    /**
     * Returns additional list of Ui component names.
     *
     * @return array
     */
    public function getAdditionalConfig()
    {
        return [
            'subProductListing' => 'tnw_subscriptionprofile_create_product_listing',
            'insertForm' => 'add_product_modal_form',
            'configurableModal' => 'configurableModal',
            'mainModal' => 'addProductsModal',
            'insertConfigurableForm' => 'add_product_modal_configurable_form',
            'configurableForm' => 'tnw_subscriptionprofile_summary_add_product_modal_configurable_form',
            'modalGrid' => 'add_product_modal_grid',
        ];
    }
}
