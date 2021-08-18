/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([], function () {
    return function (HostedFields) {
        return HostedFields.extend({
           isSubscriptionModuleEnabled() {
               let purchaseConditions = ["2", "3"];
               let purchaseType = ["One-Time Purchase"]

               let currentItems = this.purchaseCondition();
               let purchaseTypes = this.purchaseType();
               let conditionsResult = purchaseConditions.some(
                   el => currentItems.includes(el)
               );
               let typesResult = purchaseType.some(el => purchaseTypes.includes(el));

               if (purchaseTypes.length > 1
                   && conditionsResult
               ) {
                   return true;
               }

               return window.checkoutConfig.isSubscriptionEnabled
                   && conditionsResult
                   && !typesResult;
           },

           purchaseType() {
               let purchaseTypes = [];
               window.checkoutConfig.quoteGroupData.forEach(
                   element => purchaseTypes.push(
                       element.caption
                   )
               )
               return purchaseTypes;
           },

           purchaseCondition() {
               let currentItems = [];
               window.checkoutConfig.quoteItemData.forEach(
                   element => currentItems.push(
                       isNaN(element.product.tnw_subscr_purchase_type) ?
                           parseInt(element.product.tnw_subscr_purchase_type, 2)
                           : element.product.tnw_subscr_purchase_type
                   )
               )
               return currentItems;
           }
        })
    }
})
