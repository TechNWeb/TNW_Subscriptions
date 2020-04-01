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
                data.links.linked = JSON.stringify(data.links.linked);
                data.linked_product_listing = JSON.stringify(data.linked_product_listing);
                this.client.save(data, options);
                return this;
            }
        });
    }
);
