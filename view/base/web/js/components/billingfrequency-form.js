define([
        'Magento_Ui/js/form/provider'
    ],
    function (Provider) {
        'use strict';

        return Provider.extend({

            /**
             * @inheritDoc
             */
            save: function (options) {
                var data = this.get('data');
                var linked_data = data.links.linked;
                var linked = [];
                for (var key in linked_data) {
                    linked[key] = {
                        id: linked_data[key].id,
                        default_billing_frequency: linked_data[key].default_billing_frequency,
                        price: linked_data[key].price,
                        initial_fee: linked_data[key].initial_fee,
                        preset_qty: linked_data[key].preset_qty,
                        sort_order: linked_data[key].sort_order,
                    };
                }
                data.links.linked = JSON.stringify(linked);
                data.linked_product_listing = JSON.stringify(data.linked_product_listing);
                this.client.save(data, options);
                return this;
            }
        });
    }
);
