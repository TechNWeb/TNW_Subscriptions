/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

require(['prototype'], function() {
    var subscriptionInitialFee = $('subscription_initial_fee');

    if (subscriptionInitialFee.length) {
        subscriptionInitialFee.advaiceContainer = $('subscription_initial_fee_adv');
        unblockSubmit('subscription_initial_fee');
    }

    /**
     * Adds subscription initial fee bindings for submit button
     *
     * @param {String} id
     */
    function unblockSubmit(id) {
        $(id).observe('focus', function(event) {
            if ($$('button[class="scalable update-button disabled"]').size() > 0) {
                enableElements('submit-button');
            }
        });
    }
});
