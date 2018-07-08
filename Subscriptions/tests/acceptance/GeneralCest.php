<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW_Subscriptions;
use TNW_Subscriptions\AcceptanceTester;

/**
 * @group TNW
 * @group Subscriptions
 */
class GeneralCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->logInAsAdminUser();
    }

    public function _after(AcceptanceTester $I)
    {
        // TODO: Logout
    }

    public function seeMainMenuItem(AcceptanceTester $I)
    {
        $I->wantTo('See the if the main menu item is visible');
        $I->see('#menu-tnw-subscriptions-top-level');
        $I->see('mPower', '#menu-tnw-subscriptions-top-level .submenu-title');
    }
}
