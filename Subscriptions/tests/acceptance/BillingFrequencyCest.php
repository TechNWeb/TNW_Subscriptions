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
class BillingFrequencyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
        // TODO: Logout
    }

    public function validateBillingFrequencyPageName(AcceptanceTester $I)
    {
        $I->wantTo('Check the name of the Billing Frequency page');
        $I->logInAsAdminUser();
        $I->amGoingTo('Open the Billing Frequency management page');
        $I->click('.item-tnw-subscriptions-billing-frequencies a');
        $I->seeInCurrentUrl('/admin/tnw_subscriptions/billingfrequency');
        $I->amGoingTo('Check the page name');
        $I->see('Billing Frequencies', 'h1.page-title');
    }

    public function validateBillingFrequencyButtonText(AcceptanceTester $I)
    {
        $I->wantTo('Check the label on the Billing Frequency page');
        $I->logInAsAdminUser();
        $I->amGoingTo('Open the Billing Frequency management page');
        $I->click('.item-tnw-subscriptions-billing-frequencies a');
        $I->seeInCurrentUrl('/admin/tnw_subscriptions/billingfrequency');
        $I->amGoingTo('Check the text on the "add" button');
        $I->see('Add a New Billing Frequency', '#add span');
    }

//    public function createBillingFrequency(AcceptanceTester $I)
//    {
//        $I->wantTo('Create a Billing Frequency');
//        $I->logInAsAdminUser();
//        $I->amGoingTo('Open the Billing Frequency management page');
//        $I->click('.item-tnw-subscriptions-billing-frequencies a');
//        $I->seeInCurrentUrl('/admin/tnw_subscriptions/billingfrequency');
//        $I->amGoingTo('Check the "add" button');
//        $I->click('#add');
//        $I->waitForElementVisible('strong.title span', 5);
//        $I->see('General Information', 'strong.title span');
//        $I->fillField(['name' => 'label'], 'Monthly');
//        $I->selectOption(['name' => 'unit'], 'Month');
//        $I->fillField(['name' => 'frequency'], 1);
//        $I->click('#save');
//
//    }
}
