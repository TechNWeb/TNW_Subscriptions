<?php
namespace TNW_Subscriptions\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Log in as an admin user
     */
    public function logInAsAdminUser()
    {
        $I = $this->getModule('PhpBrowser');

        /**
         * If we use WebDriver (selenium) then maybe it would be worth putting those
         * tests into another suite e.g. AcceptanceSelenium. Then it would just mean
         * the browser variable would be set like this $browser = $this->getModule('WebDriver');
         */

        $I->amOnPage('/admin');
        $I->fillField('#username','admin');
        $I->fillField('#login','123123qa');
        $I->click('Sign in');
    }
}
