/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
'Magento_Ui/js/form/components/insert-form'
], function (InsertForm) {
    return InsertForm.extend({
        reRender: function () {
            this.destroyInserted().render();
        }
    })
});
