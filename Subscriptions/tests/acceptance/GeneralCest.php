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
    }

    public function _after(AcceptanceTester $I)
    {
        // TODO: Logout
    }

    public function testMagentoAdminLogin(AcceptanceTester $I) {
        $I->wantTo('Test Admin Login');
        $I->amGoingTo('Log into Magento Admin Panel');
        $I->logInAsAdminUser();
        $I->expectTo('land on the dashboard');
        $I->see('Dashboard');
        $I->seeInCurrentUrl('/admin/admin/dashboard');
    }

    public function isMainMenuItemVisible(AcceptanceTester $I)
    {
        $I->wantTo('Test General Setup');
        $I->logInAsAdminUser();
        $I->amGoingTo('Check if the mPower extension is enabled');
        $I->expectTo('see the main menu button');
        $I->seeElement('#menu-tnw-subscriptions-top-level');
        $I->see('mPower', '#menu-tnw-subscriptions-top-level .submenu-title');
    }
}
