/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['jquery'], function($) {
    $("#save_and_continue, #save").on('click', function() {
        $('body').loader('show');
    });
});
