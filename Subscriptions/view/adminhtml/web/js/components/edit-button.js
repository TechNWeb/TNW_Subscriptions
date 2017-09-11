/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/button'
], function (_, Button) {
    'use strict';

    return Button.extend({
        defaults: {
            elementTmpl: 'TNW_Subscriptions/form/element/edit-button',
            activeTitle: '',
            active: false
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe(['additionalClasses', 'activeTitle', 'active']);
        },

        /**
         * Returns list of additional classes as string.
         *
         * @returns {*}
         */
        getAdditionalClassesAsString: function () {
            var result = this.getAdditionalClasses();
            result = _.filter(_.keys(result), function (key) {
                return result[key];
            }).join(' ');
            return result;
        },

        /**
         * Returns list of additional classes as array.
         *
         * @returns {*}
         */
        getAdditionalClasses: function () {
            var result = this.additionalClasses();
            if (typeof result === 'string') {
                result = result
                    .trim()
                    .split(' ')
                    .reduce(function (classes, name) {
                        classes[name] = true;

                        return classes;
                    }, {});
            }

            return result;
        },

        /**
         * Changes button displaying.
         */
        toggle: function () {
            var result = this.getAdditionalClasses();
            result.active = !result.active;
            this.active(!this.active());
            this.additionalClasses(result);
        },

        /**
         * Activates button.
         */
        activate: function () {
            var result = this.getAdditionalClasses();
            result.active = true;
            this.active(true);
            this.additionalClasses(result);
        },

        /**
         * Deactivates button.
         */
        deactivate: function () {
            var result = this.getAdditionalClasses();
            result.active = false;
            this.active(false);
            this.additionalClasses(result);
        },

        /**
         * Returns button title.
         *
         * @returns {*}
         */
        getTitle: function () {
            return this.active() && this.activeTitle() ? this.activeTitle() : this.title();
        },

        /**
         * Checks if Update qty button should be visible and sets its visibility.
         *
         * @param previewMode
         */
        setUpdateQtyButtonVisibility: function (previewMode) {
            var currentItemData = this.source.data['item_' + this.item_id];
            var unlockPresetQty = currentItemData.unlock_preset_qty;
            var visible = false;

            if (!unlockPresetQty) {
                visible = true;
                if (!previewMode) {
                    visible = false;
                }
            }

            this.visible(visible);

        }
    });
});
