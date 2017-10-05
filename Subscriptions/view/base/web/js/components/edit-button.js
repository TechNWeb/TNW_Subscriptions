/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/button',
    'uiRegistry'
], function (_, Button, registry) {
    'use strict';

    return Button.extend({
        defaults: {
            elementTmpl: 'TNW_Subscriptions/form/element/edit-button',
            activeTitle: '',
            active: false,
            parentForm: null
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
            var parent = this.getParentForm(),
                currentItemData = {},
                unlockPresetQty = 0,
                visible = false;

            if (parent) {
                currentItemData = parent.source.data['item_' + parent.additionalData.objectItemId];
                unlockPresetQty = currentItemData.unlock_preset_qty;
            }

            if (!unlockPresetQty) {
                visible = true;
                if (!previewMode) {
                    visible = false;
                }
            }

            this.visible(visible);
        },

        getParentForm: function() {
            var parent = null;
            if (this.parentForm) {
                parent = registry.get(this.parentForm);
            }

            return parent;
        },

        hasAddons: function () {
            return false;
        },

        hasService: function () {
            return false;
        }
    });
});
