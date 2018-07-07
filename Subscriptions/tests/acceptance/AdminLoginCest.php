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
class AdminLoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        $I->amOnPage('/admin');
        $I->fillField('#username','admin');
        $I->fillField('#login','admin123');
        $I->click('Sign in');
        $I->see('Dashboard');
        $I->seeInCurrentUrl('/m2/admin/admin/dashboard');
    }
}
