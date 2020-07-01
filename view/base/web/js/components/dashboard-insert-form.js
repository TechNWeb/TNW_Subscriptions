/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/components/insert-form',
    'uiRegistry'
], function (Form, registry) {
    return Form.extend({
        onRender: function (data) {
            this._super(data);
            registry.async(
                "ns = tnw_subscriptionprofile_summary_products_form, index = overview_block"
            )(function (overview) {
                registry.get(
                    "tnw_subscriptionprofile_form.areas.dashboard.dashboard.overview.html_content"
                ).content(overview.content());
            });
        }
    })
})
