/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'mage/translate'
    ],
    function($, ko, $t) {
        'use strict';

        var steps = ko.observableArray();
        return {
            steps: steps,
            stepCodes: [],
            validCodes: [],
            currentUrl: window.location.href,

            /**
             * Handle hash
             *
             * @returns {boolean}
             */
            handleHash: function () {
                return false;
            },

            /**
             * Register step
             *
             * @param {Object} step
             */
            registerStep: function(step) {
                var code = step.code,
                    alias = (typeof step.alias == 'undefined' ? null : step.alias),
                    isActive = ko.observable(step.isActive),
                    scrollTo = (typeof step.scrollTo == 'undefined' ? null : step.scrollTo);

                if (-1 != $.inArray(code, this.validCodes)) {
                    throw new DOMException('Step code [' + code + '] already registered in step navigator');
                }
                if (alias != null) {
                    if (-1 != $.inArray(alias, this.validCodes)) {
                        throw new DOMException('Step code [' + alias + '] already registered in step navigator');
                    }
                    this.validCodes.push(alias);
                }

                this.validCodes.push(code);
                steps.push({
                    code: code,
                    alias: alias != null ? alias : code,
                    blockNamespace: step.blockNamespace,
                    requestFieldName: step.requestFieldName,
                    requestFieldValue: step.requestFieldValue,
                    title : $t(step.title),
                    sortOrder: step.sortOrder,
                    isActive: isActive,
                    scrollTo: scrollTo
                });
                this.stepCodes.push(code);

                var hash = window.location.hash.replace('#', '');
                if (hash != '' && hash != code) {
                    isActive(false);
                }
            },

            /**
             * Sort items
             *
             * @param {Object} itemOne
             * @param {Object} itemTwo
             * @returns {number}
             */
            sortItems: function(itemOne, itemTwo) {
                return itemOne.sortOrder > itemTwo.sortOrder ? 1 : -1
            },

            /**
             * Check if item processed
             *
             * @param {String} code
             * @returns {boolean}
             */
            isProcessed: function(code) {
                var activeItemIndex = this._getActiveItemIndex(),
                    sortedItems = steps.sort(this.sortItems),
                    requestedItemIndex = -1;

                sortedItems.forEach(function(element, index) {
                    if (element.code == code) {
                        requestedItemIndex = index;
                    }
                });

                return activeItemIndex > requestedItemIndex;
            },

            /**
             * Navigate back to step
             *
             * @param {String} code
             */
            navigateBackTo: function(code) {
                var self = this,
                    sortedItems = steps.sort(this.sortItems);

                if (this.isProcessed(code)) {
                    sortedItems.forEach(function(element) {
                        if (element.code == code) {
                            element.isActive(true);
                            self._applyHash(code);
                        } else {
                            element.isActive(false);
                        }
                    });
                }
            },

            /**
             * Navigate to the next step
             */
            navigateNext: function() {
                var activeIndex = 0,
                    body = $.browser.safari || $.browser.chrome ? $('body') : $('html'),
                    screenHeight = $.browser.opera? window.innerHeight : $(window).height();

                steps.sort(this.sortItems).forEach(function(element, index) {
                    if (element.isActive()) {
                        element.isActive(false);
                        activeIndex = index;
                    }
                });
                if (steps().length > activeIndex + 1) {
                    var code = steps()[activeIndex + 1].code,
                        scrollTo = steps()[activeIndex + 1].scrollTo;

                    steps()[activeIndex + 1].isActive(true);
                    this._applyHash(code);
                    if (scrollTo && $(scrollTo).length && $(scrollTo).offset().top > screenHeight) {
                        body.animate({scrollTop: $(scrollTo).offset().top}, 0);
                    }
                }
            },

            /**
             * Get active item index
             *
             * @returns {number}
             * @private
             */
            _getActiveItemIndex: function() {
                var activeIndex = 0;

                steps.sort(this.sortItems).forEach(function(element, index) {
                    if (element.isActive()) {
                        activeIndex = index;
                    }
                });

                return activeIndex;
            },

            /**
             * Apply hash to current url
             *
             * @param {String} hash
             * @private
             */
            _applyHash: function (hash) {
                var urlParts = window.location.href.split('#'),
                    url = urlParts[0];

                window.location = url + '#' + hash;
            }
        };
    }
);
