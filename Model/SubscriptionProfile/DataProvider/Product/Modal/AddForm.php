<?php
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

class AddForm extends Form
{
    /**
     * Form data scope.
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_summary_add_product_modal_form';

    /**
     * Returns additional list of Ui component names.
     *
     * @return array
     */
    public function getAdditionalConfig()
    {
        return [
            'productIndertForm' => 'products_insert_form',
            'insertForm' => 'add_product_modal_form',
            'configurableModal' => 'configurableModal',
            'mainModal' => 'addProductsModal',
            'insertConfigurableForm' => 'add_product_modal_configurable_form',
            'configurableForm' => 'tnw_subscriptionprofile_summary_add_product_modal_configurable_form',
            'modalGrid' => 'add_product_modal_grid',
            'profileErrorAria' => 'tnw_subscriptionprofile_form.areas.summary'
        ];
    }
}
