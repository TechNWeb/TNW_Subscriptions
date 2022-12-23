# TNW_Subscriptions
Magento v.2.4.x support
Magento2 Subscriptions capability

[![CircleCI](https://circleci.com/gh/PowerSync/TNW_Subscriptions.svg?style=svg&circle-token=439633811b4bbd5d3a74c6cbfb8beb017bc072ad)](https://circleci.com/gh/PowerSync/TNW_Subscriptions)

## Configuring subscription crons


Before configuring crons run in console:

cd \<magento install dir>

php bin/magento setup:upgrade

php bin/magento setup:di:compile

###Server Configuration
Configuration of crons is the same as configuration of default magento cron. See http://devdocs.magento.com/guides/v2.0/comp-mgr/prereq/prereq_cron.html
Example:

12 * * * * php <magento install dir>/bin/magento tnw_subscriptions:process --bootstrap=custom_entry_point=1 >> <magento install dir>/var/log/sub-processing.cron.log
