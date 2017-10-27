<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Create subscription profile processor.
 */
class Submit extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $profiles = $this->getSubCreateModel()->createSubscriptions();
        /** @var \TNW\Subscriptions\Model\Session\Quote $session */
        $session = $this->getSubCreateModel()->getSession();
        $session->clearStorage();
        $this->setCreatedProfilesToSession($session, $profiles);
    }

    /**
     * Setting created profiles to session.
     *
     * @param [] $profiles
     */
    private function setCreatedProfilesToSession($session, $profiles)
    {
        $profilesIds = array_map(
            function ($profile) {
                return $profile->getEntityId();
            },
            $profiles
        );
        $session->setProfileIds($profilesIds);
    }
}
