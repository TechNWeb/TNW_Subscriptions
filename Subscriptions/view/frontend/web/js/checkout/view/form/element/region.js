/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'TNW_Subscriptions/js/checkout/model/post-code-resolver'
], function (_, registry, Select, defaultPostCodeResolver) {
    'use strict';

    return Select.extend({
        defaults: {
            skipValidation: false,
            imports: {
                update: '${ $.parentName }.country_id:value'
            },
            modules: {
                country: '${ $.parentName }.country_id'
            }
        },

        /**
         * @inheritDoc
         */
        update: function (value) {
            var isRegionRequired,
                option;

            if (!value) {
                return;
            }
            option = this.country().indexedOptions[value];
            defaultPostCodeResolver.setUseDefaultPostCode(!option['is_zipcode_optional']);

            if (this.skipValidation) {
                this.validation['required-entry'] = false;
                this.required(false);
            } else {
                if (option && !option['is_region_required']) {
                    this.error(false);
                    this.validation = _.omit(this.validation, 'required-entry');
                } else {
                    this.validation['required-entry'] = true;
                }

                if (option && !this.options().length) {
                    registry.get(this.customName, function (input) {
                        isRegionRequired = !!option['is_region_required'];
                        input.validation['required-entry'] = isRegionRequired;
                        input.required(isRegionRequired);
                    });
                }

                this.required(!!option['is_region_required']);
            }
        },

        /**
         * @inheritDoc
         */
        filter: function (value, field) {
            var option;

            if (this.country) {
                option = this.country().indexedOptions[value];

                this._super(value, field);

                if (option && option['is_region_visible'] === false) {
                    // hide select and corresponding text input field if region must not be shown for selected country
                    this.setVisible(false);

                    if (this.customEntry) {// eslint-disable-line max-depth
                        this.toggleInput(false);
                    }
                }
            }
        }
    });
});
